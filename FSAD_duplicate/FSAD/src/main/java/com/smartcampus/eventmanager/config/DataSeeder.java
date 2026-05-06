package com.smartcampus.eventmanager.config;

import com.smartcampus.eventmanager.model.Event;
import com.smartcampus.eventmanager.model.EventType;
import com.smartcampus.eventmanager.repository.EventRepository;
import lombok.RequiredArgsConstructor;
import org.springframework.boot.CommandLineRunner;
import org.springframework.context.annotation.Configuration;

import java.time.LocalDateTime;
import java.util.Arrays;

@Configuration
@RequiredArgsConstructor
public class DataSeeder implements CommandLineRunner {

    private final EventRepository eventRepository;

    @Override
    public void run(String... args) throws Exception {
        if (eventRepository.count() == 0) {
            Event event1 = Event.builder()
                    .title("Cloud Computing Workshop")
                    .description("Master AWS and Azure fundamentals in this hands-on workshop.")
                    .dateTime(LocalDateTime.now().plusDays(2).withHour(10).withMinute(0))
                    .location("Tech Center Lab 4")
                    .department("Computer Science")
                    .eventType(EventType.WORKSHOP)
                    .capacity(40)
                    .currentRegistrations(0)
                    .build();

            Event event2 = Event.builder()
                    .title("Annual Tech Fest 2026")
                    .description("The biggest technology festival on campus featuring hackathons and quizzes.")
                    .dateTime(LocalDateTime.now().plusWeeks(1).withHour(9).withMinute(0))
                    .location("University Main Ground")
                    .department("All Departments")
                    .eventType(EventType.FESTIVAL)
                    .capacity(500)
                    .currentRegistrations(0)
                    .build();

            Event event3 = Event.builder()
                    .title("Seminar on AI Ethics")
                    .description("A deep dive into the ethical implications of Artificial Intelligence.")
                    .dateTime(LocalDateTime.now().plusDays(5).withHour(14).withMinute(30))
                    .location("Seminar Hall B")
                    .department("Philosophy & AI")
                    .eventType(EventType.SEMINAR)
                    .capacity(100)
                    .currentRegistrations(0)
                    .build();

            Event event4 = Event.builder()
                    .title("Robotics Design Challenge")
                    .description("Inter-departmental robotics competition for mechanical and electronics students.")
                    .dateTime(LocalDateTime.now().plusWeeks(2).withHour(9).withMinute(0))
                    .location("Robotics Lab 1")
                    .department("Mechanical")
                    .eventType(EventType.FESTIVAL)
                    .capacity(80)
                    .currentRegistrations(0)
                    .build();

            Event event5 = Event.builder()
                    .title("B-Plan Competition")
                    .description("Present your startup idea to a panel of expert investors.")
                    .dateTime(LocalDateTime.now().plusDays(10).withHour(11).withMinute(0))
                    .location("Management Block Auditorium")
                    .department("Management")
                    .eventType(EventType.WEBINAR)
                    .capacity(200)
                    .currentRegistrations(0)
                    .build();

            Event event6 = Event.builder()
                    .title("Bridge Design Seminar")
                    .description("Learning the fundamentals of modern bridge architecture.")
                    .dateTime(LocalDateTime.now().plusWeeks(3).withHour(10).withMinute(0))
                    .location("Civil Engineering Hall")
                    .department("Mechanical")
                    .eventType(EventType.SEMINAR)
                    .capacity(60)
                    .currentRegistrations(0)
                    .build();

            Event event7 = Event.builder()
                    .title("Stock Market Mastery")
                    .description("Master the basics of trading and financial analysis.")
                    .dateTime(LocalDateTime.now().plusDays(12).withHour(16).withMinute(0))
                    .location("Business Center Room 302")
                    .department("Management")
                    .eventType(EventType.WORKSHOP)
                    .capacity(120)
                    .currentRegistrations(0)
                    .build();

            eventRepository.saveAll(Arrays.asList(event1, event2, event3, event4, event5, event6, event7));
        }
    }
}
