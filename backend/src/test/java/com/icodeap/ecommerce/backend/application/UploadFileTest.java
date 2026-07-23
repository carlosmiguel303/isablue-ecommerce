package com.icodeap.ecommerce.backend.application;

import org.junit.jupiter.api.Test;
import org.junit.jupiter.api.io.TempDir;
import org.springframework.mock.web.MockMultipartFile;

import java.io.IOException;
import java.nio.file.Path;

import static org.junit.jupiter.api.Assertions.*;

/**
 * Verifica la validación de imágenes subidas: se aceptan tipos permitidos y se
 * rechazan tipos peligrosos/no soportados. Sostiene la subida de fotos de producto.
 */
class UploadFileTest {

    @Test
    void rejectsDisallowedContentType(@TempDir Path dir) {
        UploadFile uploadFile = new UploadFile(dir.toString(), "http://localhost:8085");
        MockMultipartFile exe = new MockMultipartFile(
                "image", "malware.exe", "application/octet-stream", new byte[]{1, 2, 3});

        assertThrows(IllegalArgumentException.class, () -> uploadFile.upload(exe));
    }

    @Test
    void storesAllowedImageWithRandomName(@TempDir Path dir) throws IOException {
        UploadFile uploadFile = new UploadFile(dir.toString(), "http://localhost:8085");
        MockMultipartFile png = new MockMultipartFile(
                "image", "foto.png", "image/png", new byte[]{1, 2, 3, 4});

        String url = uploadFile.upload(png);

        assertTrue(url.startsWith("http://localhost:8085/uploads/"));
        assertTrue(url.endsWith(".png"));
        assertFalse(url.contains("foto.png")); // nombre aleatorizado (no conserva el original)
    }

    @Test
    void rejectsSvgToPreventStoredXss(@TempDir Path dir) {
        UploadFile uploadFile = new UploadFile(dir.toString(), "http://localhost:8085");
        MockMultipartFile svg = new MockMultipartFile(
                "image", "logo.svg", "image/svg+xml",
                "<svg xmlns=\"http://www.w3.org/2000/svg\"><script>alert(1)</script></svg>".getBytes());

        assertThrows(IllegalArgumentException.class, () -> uploadFile.upload(svg));
    }

    @Test
    void emptyFileReturnsDefaultImage(@TempDir Path dir) throws IOException {
        UploadFile uploadFile = new UploadFile(dir.toString(), "http://localhost:8085");
        MockMultipartFile empty = new MockMultipartFile(
                "image", "", "image/png", new byte[]{});

        String url = uploadFile.upload(empty);
        assertTrue(url.endsWith("/images/default.jpg"));
    }
}
