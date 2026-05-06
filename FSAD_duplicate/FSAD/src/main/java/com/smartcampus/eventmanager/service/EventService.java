package com.smartcampus.eventmanager.service;

import com.smartcampus.eventmanager.model.Event;
import com.smartcampus.eventmanager.model.EventType;
import com.smartcampus.eventmanager.repository.EventRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;
import java.util.Optional;

@Service
@RequiredArgsConstructor
public class EventService {

    private final EventRepository eventRepository;

    public List<Event> getAllEvents() {
        return eventRepository.findAll();
    }

    public List<Event> getUpcomingEvents() {
        return eventRepository.findUpcomingEvents();
    }

    public List<Event> getLatestEvents() {
        return eventRepository.findLatestEvents();
    }

    public Optional<Event> getEventById(Long id) {
        return eventRepository.findById(id);
    }

    @Transactional
    public Event saveEvent(Event event) {
        if (event.getCurrentRegistrations() == null) {
            event.setCurrentRegistrations(0);
        }
        return eventRepository.save(event);
    }

    @Transactional
    public void deleteEvent(Long id) {
        eventRepository.deleteById(id);
    }

    public List<Event> filterByDepartment(String dept) {
        return eventRepository.findByDepartmentContainingIgnoreCase(dept);
    }

    public List<Event> filterByType(EventType type) {
        return eventRepository.findByEventType(type);
    }

    public long getTotalEvents() {
        return eventRepository.countEvents();
    }

    public Long getTotalRegistrations() {
        Long total = eventRepository.totalRegistrations();
        return total != null ? total : 0L;
    }

    public List<Event> searchEvents(String dept, EventType type) {
        return eventRepository.findByFilters(
            (dept != null && !dept.isEmpty()) ? dept : null, 
            type
        );
    }
}
