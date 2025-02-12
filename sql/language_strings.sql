-- İngilizce çeviriler
INSERT INTO language_strings (language_id, string_key, string_value) VALUES 
((SELECT id FROM languages WHERE code = 'en'), 'session_required', 'You need to be logged in.'),
((SELECT id FROM languages WHERE code = 'en'), 'upload_error_code', 'Upload error code'),
((SELECT id FROM languages WHERE code = 'en'), 'no_file_uploaded', 'No file was uploaded'),
((SELECT id FROM languages WHERE code = 'en'), 'file_upload_error', 'File upload error'),
((SELECT id FROM languages WHERE code = 'en'), 'invalid_file_type', 'Only JPG, PNG and GIF files are allowed.'),
((SELECT id FROM languages WHERE code = 'en'), 'file_size_error', 'File size cannot be larger than 5MB'),
((SELECT id FROM languages WHERE code = 'en'), 'create_uploads_dir_error', 'Could not create uploads directory'),
((SELECT id FROM languages WHERE code = 'en'), 'create_profiles_dir_error', 'Could not create profiles directory'),
((SELECT id FROM languages WHERE code = 'en'), 'profiles_dir_not_writable', 'No write permission to profiles directory'),
((SELECT id FROM languages WHERE code = 'en'), 'unknown_error', 'Unknown error'),
((SELECT id FROM languages WHERE code = 'en'), 'profile_image_updated', 'Profile image successfully updated.'),
((SELECT id FROM languages WHERE code = 'en'), 'profile_image_update_error', 'An error occurred while uploading profile image');

-- Türkçe çeviriler
INSERT INTO language_strings (language_id, string_key, string_value) VALUES 
((SELECT id FROM languages WHERE code = 'tr'), 'session_required', 'Oturum açmanız gerekiyor.'),
((SELECT id FROM languages WHERE code = 'tr'), 'upload_error_code', 'Yükleme hatası kodu'),
((SELECT id FROM languages WHERE code = 'tr'), 'no_file_uploaded', 'Dosya gönderilmedi'),
((SELECT id FROM languages WHERE code = 'tr'), 'file_upload_error', 'Dosya yükleme hatası'),
((SELECT id FROM languages WHERE code = 'tr'), 'invalid_file_type', 'Sadece JPG, PNG ve GIF dosyaları yüklenebilir.'),
((SELECT id FROM languages WHERE code = 'tr'), 'file_size_error', 'Dosya boyutu 5MB\'dan büyük olamaz'),
((SELECT id FROM languages WHERE code = 'tr'), 'create_uploads_dir_error', 'Uploads klasörü oluşturulamadı'),
((SELECT id FROM languages WHERE code = 'tr'), 'create_profiles_dir_error', 'Profiles klasörü oluşturulamadı'),
((SELECT id FROM languages WHERE code = 'tr'), 'profiles_dir_not_writable', 'Profiles dizinine yazma izni yok'),
((SELECT id FROM languages WHERE code = 'tr'), 'unknown_error', 'Bilinmeyen hata'),
((SELECT id FROM languages WHERE code = 'tr'), 'profile_image_updated', 'Profil resmi başarıyla güncellendi.'),
((SELECT id FROM languages WHERE code = 'tr'), 'profile_image_update_error', 'Profil resmi yüklenirken bir hata oluştu'); 