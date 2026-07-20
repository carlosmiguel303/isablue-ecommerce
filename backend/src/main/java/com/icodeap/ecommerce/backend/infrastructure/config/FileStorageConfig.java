package com.icodeap.ecommerce.backend.infrastructure.config;

import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Configuration;
import org.springframework.web.servlet.config.annotation.ResourceHandlerRegistry;
import org.springframework.web.servlet.config.annotation.WebMvcConfigurer;

import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;

@Configuration
public class FileStorageConfig implements WebMvcConfigurer {
    private final String uploadDirectory;

    public FileStorageConfig(@Value("${app.upload-dir:uploads}") String uploadDirectory) {
        this.uploadDirectory = uploadDirectory;
    }

    @Override
    public void addResourceHandlers(ResourceHandlerRegistry registry) {
        Path dir = Paths.get(uploadDirectory).toAbsolutePath().normalize();
        try {
            // La carpeta debe existir para que la URI termine en "/" y Spring sirva los archivos subidos.
            Files.createDirectories(dir);
        } catch (IOException ignored) {
            // Si no se puede crear aquí, se creará al subir el primer archivo.
        }
        String location = dir.toUri().toString();
        if (!location.endsWith("/")) {
            location += "/";
        }
        registry.addResourceHandler("/uploads/**").addResourceLocations(location);
    }
}
