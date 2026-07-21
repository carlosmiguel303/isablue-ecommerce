package com.icodeap.ecommerce.backend.infrastructure.adapter;

import com.icodeap.ecommerce.backend.infrastructure.entity.InfoPageEntity;
import org.springframework.data.repository.CrudRepository;

import java.util.List;
import java.util.Optional;

public interface IInfoPageCrudRepository extends CrudRepository<InfoPageEntity, Integer> {
    Optional<InfoPageEntity> findByPageKey(String pageKey);
    List<InfoPageEntity> findAllByOrderByIdAsc();
}
