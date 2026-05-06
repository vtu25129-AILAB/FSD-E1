package com.smartcampus.eventmanager.service;

import com.smartcampus.eventmanager.model.Event;
import com.smartcampus.eventmanager.model.Registration;
import com.smartcampus.eventmanager.repository.EventRepository;
import com.smartcampus.eventmanager.repository.RegistrationRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;

@Service
@RequiredArgsConstructor
public class RegistrationService {

    private final RegistrationRepository registrationRepository;
    private final EventRepository eventRepository;

    @Transactional
    public Registration registerStudent(Registration registration) {
        Event event = eventRepository.findById(registration.getEvent().getId())
                .orElseThrow(() -> new RuntimeException("Event not found"));

        if (event.getCurrentRegistrations() >= event.getCapacity()) {
            throw new RuntimeException("Event is full");
        }

        if (registrationRepository.existsByEventIdAndStudentEmail(event.getId(), registration.getStudentEmail())) {
            throw new RuntimeException("Student already registered for this event");
        }

        event.setCurrentRegistrations(event.getCurrentRegistrations() + 1);
        eventRepository.save(event);

        return registrationRepository.save(registration);
    }

    public List<Registration> getRegistrationsByEvent(Long eventId) {
        return registrationRepository.findByEventId(eventId);
    }
    
    public List<Registration> getRegistrationsByStudent(String email) {
        return registrationRepository.findByStudentEmail(email);
    }
}
