package com.icodeap.ecommerce.backend.infrastructure.rest;

import com.icodeap.ecommerce.backend.infrastructure.adapter.IInfoPageCrudRepository;
import com.icodeap.ecommerce.backend.infrastructure.entity.InfoPageEntity;
import org.springframework.web.bind.annotation.*;

/**
 * Páginas informativas públicas (formas de entrega, devoluciones, etc.).
 */
@RestController
@RequestMapping("/api/v1/home/info")
public class InfoController {

    private final IInfoPageCrudRepository repository;

    public InfoController(IInfoPageCrudRepository repository) {
        this.repository = repository;
    }

    @GetMapping("/{key}")
    public InfoPageEntity get(@PathVariable String key) {
        return repository.findByPageKey(key)
                .orElseGet(() -> repository.save(InfoPageEntity.defaultFor(key)));
    }
}
