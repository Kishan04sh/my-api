<?php

class FileUploadHelper {
    public static function upload($file, $uploadDir = 'uploads/') {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }

        // Ensure upload directory exists
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Sanitize file name
        $originalName = basename($file['name']);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeName = uniqid('file_', true) . '.' . strtolower($extension);

        $destination = rtrim($uploadDir, '/') . '/' . $safeName;

        // Move the uploaded file
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return $destination;
        } else {
            return null;
        }
    }
}
