package com.icodeap.ecommerce.backend.infrastructure.rest;

import com.icodeap.ecommerce.backend.infrastructure.adapter.IPromoCrudRepository;
import com.icodeap.ecommerce.backend.infrastructure.entity.PromoEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;

/**
 * Gestión de promociones del banner por el administrador (crear, editar, eliminar).
 * Protegido: /api/v1/admin/** requiere rol ADMIN.
 */
@RestController
@RequestMapping("/api/v1/admin/promos")
public class AdminPromoController {

    private final IPromoCrudRepository promoRepository;

    public AdminPromoController(IPromoCrudRepository promoRepository) {
        this.promoRepository = promoRepository;
    }

    @GetMapping
    public List<PromoEntity> list() {
        return promoRepository.findAllByOrderByIdAsc();
    }

    @PostMapping
    public PromoEntity create(@RequestBody PromoEntity promo) {
        promo.setId(null);
        return promoRepository.save(promo);
    }

    @PutMapping("/{id}")
    public PromoEntity update(@PathVariable Integer id, @RequestBody PromoEntity promo) {
        promo.setId(id);
        return promoRepository.save(promo);
    }

    @DeleteMapping("/{id}")
    public void delete(@PathVariable Integer id) {
        promoRepository.deleteById(id);
    }

    @PostMapping("/reset")
    public List<PromoEntity> reset() {
        promoRepository.deleteAll();
        promoRepository.saveAll(PromoEntity.defaults());
        return promoRepository.findAllByOrderByIdAsc();
    }
}
