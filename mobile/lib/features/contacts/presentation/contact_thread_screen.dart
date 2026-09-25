import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/network/api_client.dart';
import '../../../core/providers.dart';
import '../../../core/theme/app_colors.dart';
import '../../../core/theme/app_spacing.dart';
import '../../../l10n/app_localizations.dart';
import '../../../shared/widgets/error_state.dart';
import '../../../shared/widgets/skeleton.dart';
import '../data/contact_request_model.dart';
import 'contact_controller.dart';

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
  bool _sending = false;
  Timer? _live;

  @override
  void initState() {
    super.initState();
    // New visitor messages appear on their own while the conversation is open.
    _live = Timer.periodic(const Duration(seconds: 6), (_) {
      if (mounted && !_sending) {
        ref.invalidate(contactThreadProvider(widget.requestId));
      }
    });
  }

  @override
  void dispose() {
    _live?.cancel();
    _input.dispose();
    super.dispose();
  }

  Future<void> _send() async {
    final body = _input.text.trim();
    if (body.isEmpty || _sending) return;

    final messenger = ScaffoldMessenger.of(context);
    setState(() => _sending = true);
    try {
      await ref
          .read(contactRequestRepositoryProvider)
          .reply(widget.requestId, body);
      _input.clear();
      ref.invalidate(contactThreadProvider(widget.requestId));
      ref.read(contactsProvider.notifier).refresh();
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
    final async = ref.watch(contactThreadProvider(widget.requestId));
    final thread = async.valueOrNull;

    return Scaffold(
      appBar: AppBar(
        title: Text(
          (thread?.visitorName?.isNotEmpty == true)
              ? thread!.visitorName!
              : l10n.visitorAnonymous,
        ),
        actions: [
          if (thread?.visitorPhone?.isNotEmpty == true)
            IconButton(
              tooltip: l10n.callVisitor,
              icon: const Icon(Icons.call_outlined),
              onPressed: () =>
                  launchUrl(Uri(scheme: 'tel', path: thread!.visitorPhone)),
            ),
        ],
      ),
      body: async.when(
        loading: () => const SkeletonList(count: 3),
        error: (error, _) => ErrorState(
          error: error,
          onRetry: () =>
              ref.invalidate(contactThreadProvider(widget.requestId)),
        ),
        data: (t) => Column(
          children: [
            Expanded(child: _Messages(thread: t)),
            SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsets.fromLTRB(
                    AppSpacing.md, AppSpacing.sm, AppSpacing.md, AppSpacing.md),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _input,
                        minLines: 1,
                        maxLines: 4,
                        maxLength: 500,
                        buildCounter: (_,
                                {required currentLength,
                                required isFocused,
                                maxLength}) =>
                            null,
                        textInputAction: TextInputAction.newline,
                        decoration: InputDecoration(hintText: l10n.replyHint),
                      ),
                    ),
                    const SizedBox(width: AppSpacing.sm),
                    IconButton.filled(
                      tooltip: l10n.sendMessage,
                      style: IconButton.styleFrom(
                          backgroundColor: AppColors.brandYellow,
                          foregroundColor: AppColors.brandBlack),
                      onPressed: _sending ? null : _send,
                      icon: _sending
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(strokeWidth: 2))
                          : const Icon(Icons.send_rounded),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _Messages extends StatelessWidget {
  const _Messages({required this.thread});

  final ContactRequestModel thread;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context)!;
    final all = <ThreadMessage>[
      ThreadMessage(
          fromOwner: false, body: thread.message, createdAt: thread.createdAt),
      ...thread.messages,
    ];

    return ListView(
      padding: const EdgeInsets.all(AppSpacing.lg),
      children: [
        if (thread.vehicle != null)
          Center(
            child: Text(l10n.contactAboutVehicle(thread.vehicle!),
                style: const TextStyle(color: AppColors.ink500, fontSize: 12)),
          ),
        if (thread.expiresAt != null)
          Padding(
            padding: const EdgeInsets.only(top: 4, bottom: AppSpacing.md),
            child: Center(
              child: Text(
                l10n.threadExpires(DateFormat('yyyy-MM-dd')
                    .format(thread.expiresAt!.toLocal())),
                textAlign: TextAlign.center,
                style: const TextStyle(color: AppColors.ink400, fontSize: 12),
              ),
            ),
          ),
        for (final m in all) _Bubble(message: m),
      ],
    );
  }
}

class _Bubble extends StatelessWidget {
  const _Bubble({required this.message});

  final ThreadMessage message;

  @override
  Widget build(BuildContext context) {
    final mine = message.fromOwner;

    return Align(
      // "Start" is the visitor's side, "end" is mine — correct in RTL and LTR.
      alignment: mine
          ? AlignmentDirectional.centerEnd
          : AlignmentDirectional.centerStart,
      child: Container(
        margin: const EdgeInsets.only(bottom: AppSpacing.sm),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        constraints:
            BoxConstraints(maxWidth: MediaQuery.of(context).size.width * 0.78),
        decoration: BoxDecoration(
          color: mine ? AppColors.brandYellow : AppColors.ink100,
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(message.body,
                style:
                    const TextStyle(color: AppColors.brandBlack, height: 1.4)),
            const SizedBox(height: 4),
            Text(
              DateFormat('MM-dd HH:mm').format(message.createdAt.toLocal()),
              style: const TextStyle(color: AppColors.ink500, fontSize: 11),
            ),
          ],
        ),
      ),
    );
  }
}
