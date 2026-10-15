import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:image_picker/image_picker.dart';
import 'package:intl/intl.dart' hide TextDirection;
import 'package:url_launcher/url_launcher.dart';

import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/app_button.dart';
import '../../../shared/widgets/app_card.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/photo_picker.dart';
import '../../../shared/widgets/status_badge.dart';
import '../../contacts/presentation/contact_thread_screen.dart'
    show MessageComposer, MessageImage;
import '../data/support_repository.dart';

final supportWhatsappProvider =
    FutureProvider.autoDispose<List<SupportWhatsapp>>(
        (ref) => ref.watch(supportRepositoryProvider).whatsapp());

final supportTicketsProvider =
    FutureProvider.autoDispose<List<SupportTicketModel>>(
        (ref) => ref.watch(supportRepositoryProvider).tickets());

final supportTicketProvider = FutureProvider.autoDispose
    .family<SupportTicketModel, String>(
        (ref, id) => ref.watch(supportRepositoryProvider).ticket(id));

String supportCategoryLabel(AppLocalizations l10n, String key) => switch (key) {
      'general' => l10n.supportCatGeneral,
      'account' => l10n.supportCatAccount,
      'vehicle' => l10n.supportCatVehicle,
      'payment' => l10n.supportCatPayment,
      'bug' => l10n.supportCatBug,
      _ => l10n.supportCatOther,
    };

String _statusLabel(AppLocalizations l10n, String status) => switch (status) {
      'answered' => l10n.supportStatusAnswered,
      'closed' => l10n.supportStatusClosed,
      _ => l10n.supportStatusOpen,
    };

/// Technical support: WhatsApp numbers, a new ticket, and my tickets.
class SupportScreen extends ConsumerWidget {
  const SupportScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context)!;
    final numbers = ref.watch(supportWhatsappProvider);
    final tickets = ref.watch(supportTicketsProvider);

    return Scaffold(
      appBar: AppBar(title: Text(l10n.supportTitle)),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(supportWhatsappProvider);
          ref.invalidate(supportTicketsProvider);
          await ref.read(supportTicketsProvider.future);
        },
        child: ListView(
          padding: const EdgeInsets.all(AppSpacing.lg),
          children: [
            Text(l10n.supportWhatsappTitle,
                style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
            const SizedBox(height: AppSpacing.sm),
            numbers.when(
              loading: () => const Padding(
                padding: EdgeInsets.all(AppSpacing.lg),
                child: Center(child: CircularProgressIndicator()),
              ),
              error: (_, __) => Text(l10n.supportNoNumbers,
                  style: TextStyle(color: AppColors.ink500)),
              data: (list) => list.isEmpty
                  ? Text(l10n.supportNoNumbers,
                      style: TextStyle(color: AppColors.ink500))
                  : Column(
                      children: [
                        for (final n in list)
                          Padding(
                            padding:
                                const EdgeInsets.only(bottom: AppSpacing.sm),
                            child: AppCard(
                              child: InkWell(
                                borderRadius: BorderRadius.circular(12),
                                onTap: () => launchUrl(Uri.parse(n.url),
                                    mode: LaunchMode.externalApplication),
                                child: Row(
                                  children: [
                                    Container(
                                      width: 44,
                                      height: 44,
                                      decoration: BoxDecoration(
                                        color: const Color(0xFF25D366)
                                            .withValues(alpha: 0.15),
                                        shape: BoxShape.circle,
                                      ),
                                      child: const Icon(Icons.chat_rounded,
                                          color: Color(0xFF128C7E)),
                                    ),
                                    const SizedBox(width: AppSpacing.md),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          if (n.label.isNotEmpty)
                                            Text(n.label,
                                                style: const TextStyle(
                                                    fontWeight:
                                                        FontWeight.w700)),
                                          Text(n.phone,
                                              textDirection: TextDirection.ltr,
                                              style: TextStyle(
                                                  color: AppColors.ink600)),
                                        ],
                                      ),
                                    ),
                                    Icon(Icons.open_in_new_rounded,
                                        size: 20, color: AppColors.ink400),
                                  ],
                                ),
                              ),
                            ),
                          ),
                      ],
                    ),
            ),
            const SizedBox(height: AppSpacing.lg),
            AppButton(
              label: l10n.supportOpenTicket,
              icon: Icons.add_comment_outlined,
              style: AppButtonStyle.accent,
              onPressed: () => context.push('/support/new'),
            ),
            const SizedBox(height: AppSpacing.xl),
            Text(l10n.supportMyTickets,
                style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
            const SizedBox(height: AppSpacing.sm),
            tickets.when(
              loading: () => const SizedBox.shrink(),
              error: (e, _) => ErrorState(
                  error: e,
                  onRetry: () => ref.invalidate(supportTicketsProvider)),
              data: (list) => list.isEmpty
                  ? Padding(
                      padding: const EdgeInsets.symmetric(
                          vertical: AppSpacing.lg),
                      child: Text(l10n.supportNoTickets,
                          style: TextStyle(color: AppColors.ink500)),
                    )
                  : Column(
                      children: [
                        for (final t in list)
                          Padding(
                            padding:
                                const EdgeInsets.only(bottom: AppSpacing.sm),
                            child: AppCard(
                              child: InkWell(
                                borderRadius: BorderRadius.circular(12),
                                onTap: () => context.push('/support/${t.id}'),
                                child: Row(
                                  children: [
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment:
                                            CrossAxisAlignment.start,
                                        children: [
                                          Text(t.subject,
                                              maxLines: 1,
                                              overflow: TextOverflow.ellipsis,
                                              style: const TextStyle(
                                                  fontWeight: FontWeight.w700)),
                                          const SizedBox(height: 2),
                                          Text(
                                              supportCategoryLabel(
                                                  l10n, t.category),
                                              style: TextStyle(
                                                  color: AppColors.ink500,
                                                  fontSize: 12.5)),
                                          if (t.lastMessage?.isNotEmpty ==
                                              true) ...[
                                            const SizedBox(height: 4),
                                            Text(t.lastMessage!,
                                                maxLines: 1,
                                                overflow: TextOverflow.ellipsis,
                                                style: TextStyle(
                                                    color: AppColors.ink600)),
                                          ],
                                        ],
                                      ),
                                    ),
                                    const SizedBox(width: AppSpacing.sm),
                                    StatusBadge(
                                      label: _statusLabel(l10n, t.status),
                                      tone: t.isAnswered
                                          ? StatusTone.success
                                          : t.isClosed
                                              ? StatusTone.neutral
                                              : StatusTone.warning,
                                    ),
                                  ],
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

/// A new ticket: type, title, description and (optionally) a photo.
class NewTicketScreen extends ConsumerStatefulWidget {
  const NewTicketScreen({super.key});

  @override
  ConsumerState<NewTicketScreen> createState() => _NewTicketScreenState();
}

class _NewTicketScreenState extends ConsumerState<NewTicketScreen> {
  final _formKey = GlobalKey<FormState>();
  final _subject = TextEditingController();
  final _body = TextEditingController();
  String _category = 'general';
  XFile? _photo;
  bool _sending = false;
  String? _error;

  @override
  void dispose() {
    _subject.dispose();
    _body.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    setState(() {
      _sending = true;
      _error = null;
    });
    try {
      final ticket = await ref.read(supportRepositoryProvider).create(
            category: _category,
            subject: _subject.text.trim(),
            body: _body.text.trim(),
            imagePath: _photo?.path,
          );
      ref.invalidate(supportTicketsProvider);
      if (mounted) context.pushReplacement('/support/${ticket.id}');
    } catch (e) {
      if (mounted) {
        setState(() {
          _sending = false;
          _error = asApiException(e).message;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.supportNewTitle)),
      body: Form(
        key: _formKey,
        child: ListView(
          padding: const EdgeInsets.all(AppSpacing.lg),
          keyboardDismissBehavior: ScrollViewKeyboardDismissBehavior.onDrag,
          children: [
            Text(l10n.supportCategory,
                style: const TextStyle(fontWeight: FontWeight.w700)),
            const SizedBox(height: AppSpacing.sm),
            Wrap(
              spacing: AppSpacing.sm,
              runSpacing: AppSpacing.sm,
              children: [
                for (final c in supportCategories)
                  ChoiceChip(
                    label: Text(supportCategoryLabel(l10n, c)),
                    selected: _category == c,
                    onSelected: (_) => setState(() => _category = c),
                  ),
              ],
            ),
            const SizedBox(height: AppSpacing.lg),
            TextFormField(
              controller: _subject,
              maxLength: 120,
              textInputAction: TextInputAction.next,
              decoration: InputDecoration(labelText: l10n.supportSubject),
              validator: (v) =>
                  (v == null || v.trim().isEmpty) ? l10n.requiredField : null,
            ),
            const SizedBox(height: AppSpacing.md),
            TextFormField(
              controller: _body,
              maxLength: 2000,
              minLines: 5,
              maxLines: 10,
              decoration: InputDecoration(
                  labelText: l10n.supportBody, alignLabelWithHint: true),
              validator: (v) =>
                  (v == null || v.trim().isEmpty) ? l10n.requiredField : null,
            ),
            const SizedBox(height: AppSpacing.md),
            if (_photo != null)
              Padding(
                padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                child: ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: const Icon(Icons.image_outlined),
                  title: Text(_photo!.name,
                      maxLines: 1, overflow: TextOverflow.ellipsis),
                  trailing: IconButton(
                    tooltip: l10n.removeAttachment,
                    icon: const Icon(Icons.close_rounded),
                    onPressed: _sending
                        ? null
                        : () => setState(() => _photo = null),
                  ),
                ),
              ),
            AppButton(
              label: l10n.attachPhoto,
              icon: Icons.add_photo_alternate_outlined,
              style: AppButtonStyle.secondary,
              onPressed: _sending
                  ? null
                  : () async {
                      final p = await pickMessagePhoto(context);
                      if (p != null && mounted) setState(() => _photo = p);
                    },
            ),
            if (_error != null) ...[
              const SizedBox(height: AppSpacing.md),
              Text(_error!,
                  textAlign: TextAlign.center,
                  style: TextStyle(color: AppColors.statusDanger)),
            ],
            const SizedBox(height: AppSpacing.xl),
            AppButton(
              label: l10n.supportSend,
              loading: _sending,
              onPressed: _sending ? null : _submit,
            ),
          ],
        ),
      ),
    );
  }
}

/// One ticket: the conversation with the support team, photos included.
class SupportThreadScreen extends ConsumerStatefulWidget {
  const SupportThreadScreen({super.key, required this.ticketId});

  final String ticketId;

  @override
  ConsumerState<SupportThreadScreen> createState() =>
      _SupportThreadScreenState();
}

class _SupportThreadScreenState extends ConsumerState<SupportThreadScreen> {
  final _input = TextEditingController();
  final _scroll = ScrollController();
  XFile? _photo;
  bool _sending = false;
  int _lastCount = 0;

  @override
  void initState() {
    super.initState();
    _input.addListener(() => setState(() {}));
  }

  @override
  void dispose() {
    _input.dispose();
    _scroll.dispose();
    super.dispose();
  }

  void _stickToBottom(int count) {
    if (count == _lastCount) return;
    _lastCount = count;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!_scroll.hasClients) return;
      _scroll.jumpTo(_scroll.position.maxScrollExtent);
    });
  }

  Future<void> _send() async {
    final body = _input.text.trim();
    final photo = _photo;
    if ((body.isEmpty && photo == null) || _sending) return;

    final messenger = ScaffoldMessenger.of(context);
    setState(() => _sending = true);
    try {
      await ref
          .read(supportRepositoryProvider)
          .reply(widget.ticketId, body, imagePath: photo?.path);
      _input.clear();
      setState(() => _photo = null);
      ref.invalidate(supportTicketProvider(widget.ticketId));
      ref.invalidate(supportTicketsProvider);
    } catch (e) {
      messenger
          .showSnackBar(SnackBar(content: Text(asApiException(e).message)));
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final async = ref.watch(supportTicketProvider(widget.ticketId));
    final ticket = async.valueOrNull;

    return Scaffold(
      appBar: AppBar(
        title: Text(ticket?.subject ?? l10n.supportTitle,
            maxLines: 1, overflow: TextOverflow.ellipsis),
      ),
      body: async.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => ErrorState(
            error: e,
            onRetry: () =>
                ref.invalidate(supportTicketProvider(widget.ticketId))),
        data: (t) {
          _stickToBottom(t.messages.length);
          return Column(
            children: [
              Expanded(
                child: ListView(
                  controller: _scroll,
                  padding: const EdgeInsets.all(AppSpacing.lg),
                  children: [
                    for (final m in t.messages) _SupportBubble(message: m),
                  ],
                ),
              ),
              if (t.isClosed)
                SafeArea(
                  top: false,
                  child: Padding(
                    padding: const EdgeInsets.all(AppSpacing.lg),
                    child: Text(l10n.supportClosedNote,
                        textAlign: TextAlign.center,
                        style: TextStyle(color: AppColors.ink500)),
                  ),
                )
              else
                MessageComposer(
                  controller: _input,
                  sending: _sending,
                  photo: _photo,
                  onAttach: () async {
                    final p = await pickMessagePhoto(context);
                    if (p != null && mounted) setState(() => _photo = p);
                  },
                  onRemovePhoto: () => setState(() => _photo = null),
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
    );
  }
}

class _SupportBubble extends StatelessWidget {
  const _SupportBubble({required this.message});

  final SupportMessageModel message;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    // Mine on the right, support on the left — in every language.
    final mine = !message.fromStaff;
    const big = Radius.circular(20);
    const small = Radius.circular(6);

    return Align(
      alignment: mine ? Alignment.centerRight : Alignment.centerLeft,
      child: Container(
        margin: const EdgeInsets.only(bottom: AppSpacing.sm),
        padding: const EdgeInsets.fromLTRB(14, 10, 14, 8),
        constraints:
            BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.78),
        decoration: BoxDecoration(
          color: mine ? AppColors.brandYellow : AppColors.surface,
          borderRadius: BorderRadius.only(
            topLeft: big,
            topRight: big,
            bottomLeft: mine ? big : small,
            bottomRight: mine ? small : big,
          ),
          border: mine ? null : Border.all(color: AppColors.ink200),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (!mine)
              Text(l10n.supportStaff,
                  style: TextStyle(
                      color: AppColors.ink500,
                      fontSize: 11.5,
                      fontWeight: FontWeight.w700)),
            if (message.imageUrl != null) ...[
              ClipRRect(
                borderRadius: BorderRadius.circular(14),
                child: MessageImage(path: message.imageUrl!),
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
              DateFormat('yyyy-MM-dd HH:mm', 'en')
                  .format(message.createdAt.toLocal()),
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
