package com.icodeap.ecommerce.backend.application;

import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.Set;
import java.util.UUID;

public class UploadFile {
    private static final String DEFAULT_IMAGE = "default.jpg";
    private static final Set<String> ALLOWED_TYPES = Set.of("image/jpeg", "image/png", "image/webp", "image/svg+xml");

    private final Path uploadDirectory;
    private final String publicBaseUrl;

    public UploadFile(String uploadDirectory, String backendBaseUrl) {
        this.uploadDirectory = Paths.get(uploadDirectory).toAbsolutePath().normalize();
        this.publicBaseUrl = stripTrailingSlash(backendBaseUrl) + "/uploads/";
    }

    public String upload(MultipartFile multipartFile) throws IOException {
        if (multipartFile == null || multipartFile.isEmpty()) {
            return stripUploadsSuffix(publicBaseUrl) + "/images/" + DEFAULT_IMAGE;
        }

        String contentType = multipartFile.getContentType();
        if (contentType == null || !ALLOWED_TYPES.contains(contentType)) {
            throw new IllegalArgumentException("Solo se permiten imágenes JPG, PNG, WEBP o SVG");
        }

        Files.createDirectories(uploadDirectory);
        String originalName = Paths.get(multipartFile.getOriginalFilename() == null ? "imagen" : multipartFile.getOriginalFilename())
                .getFileName().toString();
        String extension = originalName.contains(".") ? originalName.substring(originalName.lastIndexOf('.')) : "";
        String storedName = UUID.randomUUID() + extension.toLowerCase();
        Path destination = uploadDirectory.resolve(storedName).normalize();

        if (!destination.startsWith(uploadDirectory)) {
            throw new IllegalArgumentException("Nombre de archivo inválido");
        }

        multipartFile.transferTo(destination);
        return publicBaseUrl + storedName;
    }

    public boolean isManagedUrl(String url) {
        return url != null && url.startsWith(publicBaseUrl);
    }

    public void deleteByUrl(String url) throws IOException {
        if (!isManagedUrl(url)) {
            return;
        }
        String filename = url.substring(publicBaseUrl.length());
        Files.deleteIfExists(uploadDirectory.resolve(Paths.get(filename).getFileName().toString()));
    }

    private static String stripTrailingSlash(String value) {
        return value != null && value.endsWith("/") ? value.substring(0, value.length() - 1) : value;
    }

    private static String stripUploadsSuffix(String value) {
        return value.endsWith("/uploads/") ? value.substring(0, value.length() - "/uploads/".length()) : value;
    }
}
