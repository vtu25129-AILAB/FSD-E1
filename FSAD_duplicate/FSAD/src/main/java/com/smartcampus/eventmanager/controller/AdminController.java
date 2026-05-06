package com.smartcampus.eventmanager.controller;

import com.smartcampus.eventmanager.model.Event;
import com.smartcampus.eventmanager.model.EventType;
import com.smartcampus.eventmanager.service.EventService;
import com.smartcampus.eventmanager.service.RegistrationService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.validation.BindingResult;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

@Controller
@RequestMapping("/admin")
@RequiredArgsConstructor
public class AdminController {

    private final EventService eventService;
    private final RegistrationService registrationService;

    @GetMapping("/dashboard")
    public String dashboard(@RequestParam(required = false) String dept,
                            @RequestParam(required = false) EventType type,
                            Model model) {
        model.addAttribute("totalEvents", eventService.getTotalEvents());
        model.addAttribute("totalRegistrations", eventService.getTotalRegistrations());
        model.addAttribute("events", eventService.searchEvents(dept, type));
        model.addAttribute("types", EventType.values());
        return "admin/dashboard";
    }

    @GetMapping("/event/new")
    public String newEventForm(Model model) {
        model.addAttribute("event", new Event());
        model.addAttribute("types", EventType.values());
        return "admin/event-form";
    }

    @GetMapping("/event/edit/{id}")
    public String editEventForm(@PathVariable Long id, Model model) {
        Event event = eventService.getEventById(id)
                .orElseThrow(() -> new RuntimeException("Event not found"));
        model.addAttribute("event", event);
        model.addAttribute("types", EventType.values());
        return "admin/event-form";
    }

    @PostMapping("/event/save")
    public String saveEvent(@Valid @ModelAttribute Event event, 
                            BindingResult result, 
                            Model model, 
                            RedirectAttributes redirectAttributes) {
        if (result.hasErrors()) {
            model.addAttribute("types", EventType.values());
            return "admin/event-form";
        }
        eventService.saveEvent(event);
        redirectAttributes.addFlashAttribute("success", "Event saved successfully!");
        return "redirect:/admin/dashboard";
    }

    @GetMapping("/event/delete/{id}")
    public String deleteEvent(@PathVariable Long id, RedirectAttributes redirectAttributes) {
        eventService.deleteEvent(id);
        redirectAttributes.addFlashAttribute("success", "Event deleted successfully!");
        return "redirect:/admin/dashboard";
    }

    @GetMapping("/event/registrations/{id}")
    public String viewRegistrations(@PathVariable Long id, Model model) {
        Event event = eventService.getEventById(id)
                .orElseThrow(() -> new RuntimeException("Event not found"));
        model.addAttribute("event", event);
        model.addAttribute("registrations", registrationService.getRegistrationsByEvent(id));
        return "admin/registrations";
    }
}
