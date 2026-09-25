import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/providers.dart';
import '../data/home_models.dart';

final homeProvider = FutureProvider.autoDispose<HomeData>((ref) {
  return ref.watch(homeRepositoryProvider).fetch();
});
