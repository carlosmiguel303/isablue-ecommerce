package com.icodeap.ecommerce.backend.domain.model;

import org.junit.jupiter.api.Test;

import java.math.BigDecimal;
import java.util.List;

import static org.junit.jupiter.api.Assertions.assertEquals;

/**
 * Verifica que el total del pedido se calcula sumando el total de cada ítem.
 * Este total es el que el backend usa para cobrar (PayPal/Culqi/Yape), por lo que
 * su exactitud es parte del flujo principal de compra.
 */
class OrderTest {

    private OrderProduct item(String quantity, String price) {
        OrderProduct op = new OrderProduct();
        op.setQuantity(new BigDecimal(quantity));
        op.setPrice(new BigDecimal(price));
        return op;
    }

    @Test
    void totalOrderPrice_sumsEachItemTotal() {
        Order order = new Order();
        order.setOrderProducts(List.of(
                item("2", "89.00"),   // 178.00
                item("1", "45.50")    //  45.50
        ));

        assertEquals(0, new BigDecimal("223.50").compareTo(order.getTotalOrderPrice()));
    }

    @Test
    void totalOrderPrice_isZeroWhenEmpty() {
        Order order = new Order();
        assertEquals(0, BigDecimal.ZERO.compareTo(order.getTotalOrderPrice()));
    }
}
