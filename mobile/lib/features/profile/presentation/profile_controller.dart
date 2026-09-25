import 'dart:typed_data';

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers.dart';
import '../data/profile_models.dart';

final profileProvider = FutureProvider.autoDispose<ProfileModel>((ref) {
  return ref.watch(profileRepositoryProvider).fetch();
});

/// The account photo bytes (null = no photo). Re-fetched whenever the
/// profile reports a different `avatarVersion`.
final profileAvatarProvider =
    FutureProvider.autoDispose<Uint8List?>((ref) async {
  final profile = await ref.watch(profileProvider.future);
  if (!profile.hasAvatar) return null;
  return ref.watch(profileRepositoryProvider).fetchAvatar();
});
