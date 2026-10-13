import 'dart:async';
import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart' hide TextDirection;
import 'package:url_launcher/url_launcher.dart';

import '../../../core/design/motion.dart';
import '../../../core/design/tokens.dart';
import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_sheet.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/skeleton.dart';
import '../data/contact_request_model.dart';
import 'contact_controller.dart';

/// A photo a visitor attached, fetched through the authenticated repository
/// (never a bare network image) and shown full-screen on tap.
class _MessageImage extends ConsumerWidget {
  const _MessageImage({required this.path});

  final String path;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final async = ref.watch(contactMessageImageProvider(path));

    return async.when(
      loading: () => SizedBox(
        width: 200,
        height: 150,
        child: Center(
            child: CircularProgressIndicator(
                strokeWidth: 2, color: AppColors.ink400)),
      ),
      error: (_, __) => Container(
        width: 200,
        height: 100,
        color: AppColors.ink100,
        alignment: Alignment.center,
        child: Icon(Icons.broken_image_outlined, color: AppColors.ink400),
      ),
      data: (bytes) => GestureDetector(
        onTap: () => showDialog<void>(
          context: context,
          builder: (context) => Dialog(
            insetPadding: const EdgeInsets.all(AppSpacing.md),
            backgroundColor: Colors.transparent,
            child: GestureDetector(
              onTap: () => Navigator.of(context).pop(),
              child: InteractiveViewer(child: Image.memory(bytes)),
            ),
          ),
        ),
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxHeight: 220, maxWidth: 220),
          child: Image.memory(bytes, fit: BoxFit.cover),
        ),
      ),
    );
  }
}

/// One visitor conversation: their first message, then replies from both
/// sides. The owner replies here; the visitor sees the reply on the page
/// they scanned. The whole conversation is deleted automatically 15 days
/// after its last message, and the screen says when.
class ContactThreadScreen extends ConsumerStatefulWidget {
  const ContactThreadScreen({super.key, required this.requestId});

  final String requestId;

  @override
  ConsumerState<ContactThreadScreen> createState() =>
      _ContactThreadScreenState();
}

class _ContactThreadScreenState extends ConsumerState<ContactThreadScreen> {
  final _input = TextEditingController();
  final _scroll = ScrollController();
  bool _sending = false;
  XFile? _picked;
  Timer? _live;
  int _lastCount = 0;

  @override
  void initState() {
    super.initState();
    // New visitor messages appear on their own while the conversation is open.
    _live = Timer.periodic(const Duration(seconds: 6), (_) {
      if (mounted && !_sending) {
        ref.invalidate(contactThreadProvider(widget.requestId));
      }
    });
    _input.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _live?.cancel();
    _input.dispose();
    _scroll.dispose();
    super.dispose();
  }

  /// Keeps the newest message in view when one arrives or is sent.
  void _stickToBottom(int count) {
    if (count == _lastCount) return;
    _lastCount = count;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scroll.hasClients) return;
      _scroll.animateTo(_scroll.position.maxScrollExtent,
          duration: AppMotion.base, curve: AppMotion.enter);
    });
  }

  Future<void> _send() async {
    final body = _input.text.trim();
    final photo = _picked;
    if ((body.isEmpty && photo == null) || _sending) return;

    HapticFeedback.lightImpact();
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _sending = true);
    try {
      await ref
          .read(contactRequestRepositoryProvider)
          .reply(widget.requestId, body, imagePath: photo?.path);
      _input.clear();
      _picked = null;
      ref.invalidate(contactThreadProvider(widget.requestId));
      ref.read(contactsProvider.notifier).refresh();
    } catch (e) {
      messenger
          .showSnackBar(SnackBar(content: Text(asApiException(e).message)));
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  static const _maxImageBytes = 8 * 1024 * 1024;
  static const _allowedExtensions = {'jpg', 'jpeg', 'png', 'webp'};

  /// Camera or photo library -> a preview above the composer. Validated here
  /// (type, size) for a fast, clear message; the server re-checks both and
  /// re-encodes the image, so this is a convenience, not the security boundary.
  Future<void> _pickPhoto() async {
    final l10n = AppLocalizations.of(context)!;
    final source = await showAppSheet<ImageSource>(
      context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_camera_outlined),
              title: Text(l10n.takePhoto),
              onTap: () => Navigator.of(context).pop(ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: Text(l10n.chooseFromGallery),
              onTap: () => Navigator.of(context).pop(ImageSource.gallery),
            ),
          ],
        ),
      ),
    );
    if (source == null || !mounted) return;

    final messenger = ScaffoldMessenger.of(context);
    try {
      // Re-encoding to a bounded JPEG here also turns iPhone HEIC into JPEG.
      final picked = await ImagePicker().pickImage(
          source: source, maxWidth: 1600, maxHeight: 1600, imageQuality: 85);
      if (picked == null || !mounted) return;

      final ext = picked.name.contains('.')
          ? picked.name.split('.').last.toLowerCase()
          : '';
      if (!_allowedExtensions.contains(ext)) {
        messenger
            .showSnackBar(SnackBar(content: Text(l10n.imageTypeUnsupported)));
        return;
      }
      if (await File(picked.path).length() > _maxImageBytes) {
        messenger.showSnackBar(SnackBar(content: Text(l10n.imageTooLarge)));
        return;
      }
      setState(() => _picked = picked);
    } on PlatformException catch (e) {
      final denied = e.code.contains('access_denied');
      messenger.showSnackBar(SnackBar(
          content: Text(!denied
              ? l10n.imageSendFailed
              : source == ImageSource.camera
                  ? l10n.cameraPermissionDenied
                  : l10n.photosPermissionDenied)));
    } catch (_) {
      messenger.showSnackBar(SnackBar(content: Text(l10n.imageSendFailed)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final async = ref.watch(contactThreadProvider(widget.requestId));
    final thread = async.valueOrNull;
    final named = thread?.visitorName?.isNotEmpty == true;

    return Scaffold(
      appBar: AppBar(
        titleSpacing: 0,
        title: Row(
          children: [
            Container(
              width: 38,
              height: 38,
              decoration: BoxDecoration(
                  shape: BoxShape.circle, color: AppColors.brandYellow),
              alignment: Alignment.center,
              child: named
                  ? Text(thread!.visitorName!.characters.first,
                      style: const TextStyle(
                          color: AppColors.brandBlack,
                          fontWeight: FontWeight.w800,
                          fontSize: 16))
                  : const Icon(Icons.person_outline_rounded,
                      color: AppColors.brandBlack, size: 22),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(named ? thread!.visitorName! : l10n.visitorAnonymous,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          fontWeight: FontWeight.w800, fontSize: 16)),
                  if (thread?.vehicle != null)
                    Text(l10n.contactAboutVehicle(thread!.vehicle!),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: TextStyle(
                            color: AppColors.ink500,
                            fontSize: 12,
                            fontWeight: FontWeight.w500)),
                ],
              ),
            ),
          ],
        ),
        actions: [
          if (thread?.visitorPhone?.isNotEmpty == true)
            IconButton(
              tooltip: l10n.callVisitor,
              icon: const Icon(Icons.call_rounded),
              onPressed: () =>
                  launchUrl(Uri(scheme: 'tel', path: thread!.visitorPhone)),
            ),
        ],
      ),
      body: SwapFade(
        child: async.when(
          loading: () => ListView(
            key: const ValueKey('loading'),
            physics: const NeverScrollableScrollPhysics(),
            padding: const EdgeInsets.all(AppSpacing.lg),
            children: const [
              Align(
                  alignment: Alignment.centerRight,
                  child: SkeletonBox(width: 220, height: 60, radius: 20)),
              SizedBox(height: AppSpacing.md),
              Align(
                  alignment: Alignment.centerLeft,
                  child: SkeletonBox(width: 160, height: 46, radius: 20)),
            ],
          ),
          error: (error, _) => ErrorState(
            key: const ValueKey('error'),
            error: error,
            onRetry: () =>
                ref.invalidate(contactThreadProvider(widget.requestId)),
          ),
          data: (t) {
            _stickToBottom(t.messages.length);
            return Column(
              key: const ValueKey('data'),
              children: [
                Expanded(child: _Messages(thread: t, controller: _scroll)),
                _Composer(
                  controller: _input,
                  sending: _sending,
                  photo: _picked,
                  onAttach: _pickPhoto,
                  onRemovePhoto: () => setState(() => _picked = null),
                  attachTooltip: l10n.attachPhoto,
                  removeTooltip: l10n.removeAttachment,
                  hint: l10n.replyHint,
                  tooltip: l10n.sendMessage,
                  onSend: _send,
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _Composer extends StatelessWidget {
  const _Composer({
    required this.controller,
    required this.sending,
    required this.photo,
    required this.onAttach,
    required this.onRemovePhoto,
    required this.attachTooltip,
    required this.removeTooltip,
    required this.hint,
    required this.tooltip,
    required this.onSend,
  });

  final TextEditingController controller;
  final bool sending;
  final XFile? photo;
  final VoidCallback onAttach;
  final VoidCallback onRemovePhoto;
  final String attachTooltip;
  final String removeTooltip;
  final String hint;
  final String tooltip;
  final VoidCallback onSend;

  @override
  Widget build(BuildContext context) {
    final canSend =
        (controller.text.trim().isNotEmpty || photo != null) && !sending;

    return SafeArea(
      top: false,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(
            AppSpacing.md, AppSpacing.sm, AppSpacing.md, AppSpacing.md),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (photo != null)
              Padding(
                padding:
                    const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
                child: Stack(
                  clipBehavior: Clip.none,
                  children: [
                    ClipRRect(
                      borderRadius: BorderRadius.circular(14),
                      child: Image.file(File(photo!.path),
                          width: 96,
                          height: 96,
                          fit: BoxFit.cover,
                          errorBuilder: (_, __, ___) => Container(
                              width: 96,
                              height: 96,
                              color: AppColors.ink100,
                              child: Icon(Icons.broken_image_outlined,
                                  color: AppColors.ink400))),
                    ),
                    PositionedDirectional(
                      top: -8,
                      end: -8,
                      child: Semantics(
                        button: true,
                        label: removeTooltip,
                        child: Pressable(
                          onTap: sending ? null : onRemovePhoto,
                          scale: 0.9,
                          child: Container(
                            width: 28,
                            height: 28,
                            decoration: const BoxDecoration(
                                shape: BoxShape.circle,
                                color: AppColors.brandBlack),
                            child: const Icon(Icons.close_rounded,
                                size: 16, color: AppColors.brandWhite),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            Container(
              padding: const EdgeInsetsDirectional.only(
                  start: 4, end: 6, top: 4, bottom: 4),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(28),
                boxShadow: AppShadows.card,
                border: AppShadows.hairline,
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Semantics(
                    button: true,
                    label: attachTooltip,
                    child: Pressable(
                      onTap: sending ? null : onAttach,
                      scale: 0.9,
                      child: SizedBox(
                        width: 46,
                        height: 46,
                        child: Icon(Icons.add_photo_alternate_outlined,
                            size: 24, color: AppColors.ink500),
                      ),
                    ),
                  ),
                  Expanded(
                    child: TextField(
                      controller: controller,
                      minLines: 1,
                      maxLines: 5,
                      maxLength: 500,
                      buildCounter: (_,
                              {required currentLength,
                              required isFocused,
                              maxLength}) =>
                          null,
                      textInputAction: TextInputAction.newline,
                      decoration: InputDecoration(
                        hintText: hint,
                        filled: false,
                        border: InputBorder.none,
                        enabledBorder: InputBorder.none,
                        focusedBorder: InputBorder.none,
                        contentPadding:
                            const EdgeInsets.symmetric(vertical: 12),
                      ),
                    ),
                  ),
                  const SizedBox(width: 6),
                  Semantics(
                    button: true,
                    label: tooltip,
                    child: Pressable(
                      onTap: canSend ? onSend : null,
                      scale: 0.9,
                      child: AnimatedContainer(
                        duration: AppMotion.base,
                        curve: AppMotion.standard,
                        width: 46,
                        height: 46,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: canSend
                              ? AppColors.brandYellow
                              : AppColors.ink100,
                        ),
                        child: AnimatedSwitcher(
                          duration: AppMotion.fast,
                          child: sending
                              ? const Padding(
                                  key: ValueKey('sending'),
                                  padding: EdgeInsets.all(13),
                                  child: CircularProgressIndicator(
                                      strokeWidth: 2.2,
                                      color: AppColors.brandBlack),
                                )
                              : Transform.flip(
                                  key: const ValueKey('send'),
                                  // The send glyph points right; in RTL it must point left.
                                  flipX: Directionality.of(context) ==
                                      TextDirection.rtl,
                                  child: Icon(Icons.send_rounded,
                                      size: 22,
                                      color: canSend
                                          ? AppColors.brandBlack
                                          : AppColors.ink400)),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Messages extends StatelessWidget {
  const _Messages({required this.thread, required this.controller});

  final ContactRequestModel thread;
  final ScrollController controller;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final all = <ThreadMessage>[
      ThreadMessage(
          fromOwner: false, body: thread.message, createdAt: thread.createdAt),
      ...thread.messages,
    ];

    return ListView(
      controller: controller,
      padding: const EdgeInsets.fromLTRB(
          AppSpacing.lg, AppSpacing.md, AppSpacing.lg, AppSpacing.lg),
      children: [
        if (thread.expiresAt != null)
          Center(
            child: Container(
              margin: const EdgeInsets.only(bottom: AppSpacing.lg),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              decoration: BoxDecoration(
                color: AppColors.ink100,
                borderRadius: BorderRadius.circular(AppRadius.pill),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(Icons.timer_outlined, size: 14, color: AppColors.ink500),
                  const SizedBox(width: 6),
                  Flexible(
                    child: Text(
                      l10n.threadExpires(DateFormat('yyyy-MM-dd', 'en')
                          .format(thread.expiresAt!.toLocal())),
                      textAlign: TextAlign.center,
                      style: TextStyle(color: AppColors.ink500, fontSize: 11.5),
                    ),
                  ),
                ],
              ),
            ),
          ),
        for (var i = 0; i < all.length; i++)
          FadeSlideIn(
            index: i > 5 ? 0 : i,
            offset: 12,
            child: _Bubble(message: all[i]),
          ),
      ],
    );
  }
}

class _Bubble extends ConsumerWidget {
  const _Bubble({required this.message});

  final ThreadMessage message;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final mine = message.fromOwner;
    // The owner is always on the right and the visitor on the left, in every language
    // (physical sides, not start/end). The corner nearest the sender is squared off, like a
    // speech tail.
    const big = Radius.circular(20);
    const small = Radius.circular(6);
    final radius = BorderRadius.only(
      topLeft: big,
      topRight: big,
      bottomLeft: mine ? big : small,
      bottomRight: mine ? small : big,
    );

    return Align(
      alignment: mine ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: AppSpacing.sm),
        padding: const EdgeInsets.fromLTRB(14, 10, 14, 8),
        constraints:
            BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.78),
        decoration: BoxDecoration(
          color: mine ? AppColors.brandYellow : AppColors.surface,
          borderRadius: radius,
          boxShadow: mine ? null : AppShadows.card,
          border: mine ? null : AppShadows.hairline,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (message.imageUrl != null) ...[
              ClipRRect(
                borderRadius: BorderRadius.circular(14),
                child: _MessageImage(path: message.imageUrl!),
              ),
              if (message.body.isNotEmpty) const SizedBox(height: 6),
            ],
            if (message.body.isNotEmpty)
              Text(message.body,
                  style: TextStyle(
                      color: mine ? AppColors.brandBlack : AppColors.ink900,
                      fontSize: 15,
                      height: 1.45)),
            const SizedBox(height: 4),
            Text(
              DateFormat('HH:mm', 'en').format(message.createdAt.toLocal()),
              style: TextStyle(
                  color: mine
                      ? AppColors.brandBlack.withValues(alpha: 0.55)
                      : AppColors.ink400,
                  fontSize: 11),
            ),
          ],
        ),
      ),
    );
  }
}
