package com.icodeap.ecommerce.backend.infrastructure.adapter;

import com.icodeap.ecommerce.backend.infrastructure.entity.PaymentEntity;
import org.springframework.data.repository.CrudRepository;

import java.util.List;

public interface IPaymentCrudRepository extends CrudRepository<PaymentEntity, Integer> {
    List<PaymentEntity> findAllByOrderByIdDesc();
}
