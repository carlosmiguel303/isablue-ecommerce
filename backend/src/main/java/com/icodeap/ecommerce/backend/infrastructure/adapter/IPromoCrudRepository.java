package com.icodeap.ecommerce.backend.infrastructure.adapter;

import com.icodeap.ecommerce.backend.infrastructure.entity.PromoEntity;
import org.springframework.data.repository.CrudRepository;

import java.util.List;

public interface IPromoCrudRepository extends CrudRepository<PromoEntity, Integer> {
    List<PromoEntity> findAllByOrderByIdAsc();
}
