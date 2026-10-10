#!/bin/sh
# Xcode Cloud runs this right after cloning, before it resolves packages.
# A fresh clone has no Flutter SDK and no ios/Flutter/ephemeral (generated, not
# committed), so SwiftPM fails with "FlutterGeneratedPluginSwiftPackage doesn't
# exist". Install Flutter and generate that folder here.
set -e

cd "$CI_PRIMARY_REPOSITORY_PATH"

git clone https://github.com/flutter/flutter.git --depth 1 -b stable "$HOME/flutter"
export PATH="$PATH:$HOME/flutter/bin"

flutter --version
flutter precache --ios
flutter pub get

# Writes Generated.xcconfig and ios/Flutter/ephemeral/Packages/* (no signing here;
# Xcode Cloud signs the archive itself).
flutter build ios --config-only --no-codesign

# Plugins without Swift Package Manager support still come through CocoaPods.
if [ -f ios/Podfile ]; then
  HOMEBREW_NO_AUTO_UPDATE=1 brew install cocoapods
  (cd ios && pod install)
fi

exit 0
