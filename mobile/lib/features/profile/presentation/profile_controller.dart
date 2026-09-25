import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers.dart';
import '../data/profile_models.dart';

final profileProvider = FutureProvider.autoDispose<ProfileModel>((ref) {
  return ref.watch(profileRepositoryProvider).fetch();
});
