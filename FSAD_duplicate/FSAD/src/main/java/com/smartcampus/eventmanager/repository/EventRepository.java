package com.smartcampus.eventmanager.repository;

import com.smartcampus.eventmanager.model.Event;
import com.smartcampus.eventmanager.model.EventType;
import org.springframework.data.jpa.repository.JpaRepository;
import org.springframework.data.jpa.repository.Query;
import org.springframework.stereotype.Repository;

import java.util.List;

@Repository
public interface EventRepository extends JpaRepository<Event, Long> {

    @Query("SELECT e FROM Event e WHERE " +
           "(:dept IS NULL OR LOWER(e.department) LIKE LOWER(CONCAT('%', :dept, '%'))) AND " +
           "(:type IS NULL OR e.eventType = :type)")
    List<Event> findByFilters(String dept, EventType type);

    List<Event> findByDepartmentContainingIgnoreCase(String department);
    List<Event> findByEventType(EventType eventType);
    
    @Query("SELECT e FROM Event e WHERE e.dateTime >= CURRENT_TIMESTAMP ORDER BY e.dateTime ASC")
    List<Event> findUpcomingEvents();

    @Query("SELECT e FROM Event e ORDER BY e.createdAt DESC")
    List<Event> findLatestEvents();

    @Query("SELECT COUNT(e) FROM Event e")
    long countEvents();

    @Query("SELECT SUM(e.currentRegistrations) FROM Event e")
    Long totalRegistrations();
}
