package com.smartcampus.eventmanager.repository;

import com.smartcampus.eventmanager.model.Registration;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.stereotype.Repository;

import java.util.List;

@Repository
public interface RegistrationRepository extends JpaRepository<Registration, Long> {
    List<Registration> findByEventId(Long eventId);
    List<Registration> findByStudentEmail(String email);
    boolean existsByEventIdAndStudentEmail(Long eventId, String email);
}
