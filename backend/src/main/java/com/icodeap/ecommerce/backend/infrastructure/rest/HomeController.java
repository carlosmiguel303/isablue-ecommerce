package com.icodeap.ecommerce.backend.infrastructure.rest;

import com.icodeap.ecommerce.backend.application.CategoryService;
import com.icodeap.ecommerce.backend.application.ProductService;
import com.icodeap.ecommerce.backend.domain.model.Category;
import com.icodeap.ecommerce.backend.domain.model.Product;
import com.icodeap.ecommerce.backend.infrastructure.adapter.IPromoCrudRepository;
import com.icodeap.ecommerce.backend.infrastructure.entity.PromoEntity;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/v1/home")
public class HomeController {
    private final ProductService productService;
    private final CategoryService categoryService;
    private final IPromoCrudRepository promoRepository;

    public HomeController(ProductService productService, CategoryService categoryService,
                          IPromoCrudRepository promoRepository) {
        this.productService = productService;
        this.categoryService = categoryService;
        this.promoRepository = promoRepository;
    }

    @GetMapping
    public ResponseEntity<Iterable<Product>> findAll(){
        return ResponseEntity.ok(productService.findAll());
    }

    @GetMapping("/{id}")
    public ResponseEntity<Product> findById(@PathVariable Integer id){
        return ResponseEntity.ok(productService.findById(id));
    }

    /** Categorías públicas para el menú de la tienda (se actualiza al crear secciones en el admin). */
    @GetMapping("/categories")
    public ResponseEntity<Iterable<Category>> categories(){
        return ResponseEntity.ok(categoryService.findAll());
    }

    /** Promociones del banner, visibles para todas las visitas. */
    @GetMapping("/promos")
    public ResponseEntity<List<PromoEntity>> promos(){
        List<PromoEntity> list = promoRepository.findAllByOrderByIdAsc();
        if (list.isEmpty()) {
            promoRepository.saveAll(PromoEntity.defaults());
            list = promoRepository.findAllByOrderByIdAsc();
        }
        return ResponseEntity.ok(list);
    }
}
