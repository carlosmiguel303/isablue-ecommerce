package com.icodeap.ecommerce.backend.infrastructure.rest;

import com.icodeap.ecommerce.backend.infrastructure.adapter.IInfoPageCrudRepository;
import com.icodeap.ecommerce.backend.infrastructure.entity.InfoPageEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;

/**
 * Edición de las páginas informativas por el administrador.
 * Protegido: /api/v1/admin/** requiere rol ADMIN.
 */
@RestController
@RequestMapping("/api/v1/admin/info")
public class AdminInfoController {

    private final IInfoPageCrudRepository repository;

    public AdminInfoController(IInfoPageCrudRepository repository) {
        this.repository = repository;
    }

    @GetMapping
    public List<InfoPageEntity> list() {
        // Asegura que existan las páginas por defecto para editarlas.
        for (InfoPageEntity def : InfoPageEntity.defaults()) {
            if (repository.findByPageKey(def.getPageKey()).isEmpty()) {
                repository.save(def);
            }
        }
        return repository.findAllByOrderByIdAsc();
    }

    @PutMapping("/{key}")
    public InfoPageEntity update(@PathVariable String key, @RequestBody InfoPageEntity body) {
        InfoPageEntity page = repository.findByPageKey(key).orElseGet(() -> InfoPageEntity.defaultFor(key));
        page.setPageKey(key);
        page.setTitle(body.getTitle());
        page.setContent(body.getContent());
        return repository.save(page);
    }
}
