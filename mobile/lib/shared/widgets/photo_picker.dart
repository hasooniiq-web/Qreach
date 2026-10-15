import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';

import '../../l10n/app_localizations.dart';
import 'app_sheet.dart';

const _maxImageBytes = 8 * 1024 * 1024;
const _allowedExtensions = {'jpg', 'jpeg', 'png', 'webp'};

/// Camera or photo library -> one photo, checked here (type, size) for a fast and
/// clear message. The server re-checks both and re-encodes the image, so this is a
/// convenience, not the security boundary. Returns null when nothing was picked.
Future<XFile?> pickMessagePhoto(BuildContext context) async {
  final l10n = AppLocalizations.of(context)!;
  final messenger = ScaffoldMessenger.of(context);

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
  if (source == null) return null;

  try {
    // Re-encoding to a bounded JPEG here also turns iPhone HEIC into JPEG.
    final picked = await ImagePicker().pickImage(
        source: source, maxWidth: 1600, maxHeight: 1600, imageQuality: 85);
    if (picked == null) return null;

    final ext = picked.name.contains('.')
        ? picked.name.split('.').last.toLowerCase()
        : '';
    if (!_allowedExtensions.contains(ext)) {
      messenger.showSnackBar(SnackBar(content: Text(l10n.imageTypeUnsupported)));
      return null;
    }
    if (await File(picked.path).length() > _maxImageBytes) {
      messenger.showSnackBar(SnackBar(content: Text(l10n.imageTooLarge)));
      return null;
    }
    return picked;
  } on PlatformException catch (e) {
    final denied = e.code.contains('access_denied');
    messenger.showSnackBar(SnackBar(
        content: Text(!denied
            ? l10n.imageSendFailed
            : source == ImageSource.camera
                ? l10n.cameraPermissionDenied
                : l10n.photosPermissionDenied)));
    return null;
  } catch (_) {
    messenger.showSnackBar(SnackBar(content: Text(l10n.imageSendFailed)));
    return null;
  }
}
