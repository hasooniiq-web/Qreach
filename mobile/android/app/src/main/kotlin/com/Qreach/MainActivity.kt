package com.Qreach

import io.flutter.embedding.android.FlutterFragmentActivity

// FlutterFragmentActivity, not FlutterActivity: local_auth's Android
// implementation shows the biometric prompt via a Fragment and requires it.
class MainActivity : FlutterFragmentActivity()
