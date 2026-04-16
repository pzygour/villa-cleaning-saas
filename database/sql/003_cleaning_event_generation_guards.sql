-- Milestone 2 safety guard for generated event duplication.
-- Note: booking_id can be NULL for departure_arrival; this key mainly protects booking-linked events.
ALTER TABLE cleaning_events
  ADD UNIQUE KEY uq_cleaning_event_generated_booking_scope (
    property_id,
    event_date,
    event_type,
    booking_id,
    generated_by
  );
