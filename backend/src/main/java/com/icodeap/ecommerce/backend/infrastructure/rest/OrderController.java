package com.icodeap.ecommerce.backend.infrastructure.rest;

import com.icodeap.ecommerce.backend.application.OrderService;
import com.icodeap.ecommerce.backend.application.ProductService;
import com.icodeap.ecommerce.backend.application.UserService;
import com.icodeap.ecommerce.backend.domain.model.Order;
import com.icodeap.ecommerce.backend.domain.model.OrderProduct;
import com.icodeap.ecommerce.backend.domain.model.OrderState;
import com.icodeap.ecommerce.backend.domain.model.User;
import com.icodeap.ecommerce.backend.infrastructure.exception.BusinessException;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.Authentication;
import org.springframework.web.bind.annotation.*;

import java.math.BigDecimal;

@RestController
@RequestMapping("/api/v1/orders")
public class OrderController {
    private final OrderService orderService;
    private final UserService userService;
    private final ProductService productService;

    public OrderController(OrderService orderService, UserService userService, ProductService productService) {
        this.orderService = orderService;
        this.userService = userService;
        this.productService = productService;
    }

    @PostMapping
    public ResponseEntity<Order> save(@RequestBody Order order, Authentication authentication) {
        User currentUser = userService.findByEmail(authentication.getName());
        order.setUserId(currentUser.getId());
        order.setOrderState(OrderState.PENDING);

        if (order.getOrderProducts() == null || order.getOrderProducts().isEmpty()) {
            throw new BusinessException("La orden debe contener al menos un producto");
        }

        for (OrderProduct item : order.getOrderProducts()) {
            if (item.getQuantity() == null || item.getQuantity().compareTo(BigDecimal.ONE) < 0 || item.getQuantity().compareTo(BigDecimal.TEN) > 0) {
                throw new BusinessException("La cantidad debe estar entre 1 y 10 unidades");
            }
            item.setPrice(productService.findById(item.getProductId()).getPrice());
        }

        return ResponseEntity.status(HttpStatus.CREATED).body(orderService.save(order));
    }

    @PostMapping("/update/state/order")
    public ResponseEntity<Void> updateStateById(@RequestParam Integer id, @RequestParam String state) {
        orderService.updateStateById(id, state);
        return ResponseEntity.ok().build();
    }

    @GetMapping
    public ResponseEntity<Iterable<Order>> findAll() {
        return ResponseEntity.ok(orderService.findAll());
    }

    @GetMapping("/{id}")
    public ResponseEntity<Order> findById(@PathVariable Integer id, Authentication authentication) {
        Order order = orderService.findById(id);
        User currentUser = userService.findByEmail(authentication.getName());
        boolean isAdmin = authentication.getAuthorities().stream().anyMatch(a -> "ROLE_ADMIN".equals(a.getAuthority()));

        if (!isAdmin && !order.getUserId().equals(currentUser.getId())) {
            throw new BusinessException("No tienes acceso a esta orden");
        }
        return ResponseEntity.ok(order);
    }

    @GetMapping("/mine")
    public ResponseEntity<Iterable<Order>> findMine(Authentication authentication) {
        User currentUser = userService.findByEmail(authentication.getName());
        return ResponseEntity.ok(orderService.findByUserId(currentUser.getId()));
    }
}
