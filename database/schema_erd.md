# NomReNom – Database Architecture & Entity Relationship Diagram (ERD)

This document describes the schema design, table structures, and entity relationships of the `nomrenom_db` MySQL database for the **NomReNom** surplus-food marketplace.

---

## 1. Entity Relationship Diagram (ERD)

```mermaid
erDiagram
    users {
        int id PK
        string full_name
        string email UK
        string phone
        string password_hash
        enum role
        tinyint is_email_verified
        tinyint is_phone_verified
        string remember_token
        datetime created_at
        datetime updated_at
    }

    guest_customers {
        int id PK
        string full_name
        string email
        string phone
        tinyint is_email_verified
        tinyint is_phone_verified
        datetime created_at
    }

    bakeries {
        int id PK
        string name
        string slug UK
        text description
        string address
        string district
        string city
        string phone
        string email
        time opening_time
        time closing_time
        string logo_url
        string image_url
        tinyint is_active
        datetime created_at
        datetime updated_at
    }

    food_boxes {
        int id PK
        int bakery_id FK
        string name
        string slug UK
        enum box_type
        decimal price
        decimal original_value
        text description
        text contents_sample
        string image_url
        int available_qty
        tinyint is_active
        datetime created_at
        datetime updated_at
    }

    pickup_slots {
        int id PK
        int bakery_id FK
        date pickup_date
        time start_time
        time end_time
        int max_capacity
        int reserved_count
        tinyint is_active
        datetime created_at
    }

    reservations {
        int id PK
        string reservation_code UK
        int user_id FK
        int guest_id FK
        int bakery_id FK
        int pickup_slot_id FK
        decimal total_amount
        enum reservation_status
        enum payment_method
        enum payment_status
        string special_instructions
        datetime collected_at
        datetime cancelled_at
        string cancellation_reason
        datetime created_at
        datetime updated_at
    }

    reservation_items {
        int id PK
        int reservation_id FK
        int food_box_id FK
        string box_name
        decimal unit_price
        int quantity
        decimal subtotal
    }

    payments {
        int id PK
        int reservation_id FK
        enum payment_method
        enum gateway_provider
        string transaction_reference
        decimal amount
        enum payment_status
        text gateway_response
        datetime payment_date
        datetime created_at
    }

    email_verification_tokens {
        int id PK
        int user_id FK
        int guest_id FK
        string email
        string token UK
        datetime expires_at
        tinyint is_used
        datetime created_at
    }

    phone_verification_tokens {
        int id PK
        string phone
        string otp_code
        datetime expires_at
        tinyint is_used
        datetime created_at
    }

    password_reset_tokens {
        int id PK
        int user_id FK
        string token UK
        datetime expires_at
        tinyint is_used
        datetime created_at
    }

    feedback {
        int id PK
        int reservation_id FK
        int user_id FK
        int guest_id FK
        int food_box_id FK
        string customer_name
        tinyint rating
        text comment
        tinyint is_public
        datetime created_at
    }

    admin_activity_logs {
        int id PK
        int admin_user_id FK
        string action
        string entity_type
        int entity_id
        text details
        string ip_address
        datetime created_at
    }

    bakeries ||--o{ food_boxes : "offers"
    bakeries ||--o{ pickup_slots : "schedules"
    bakeries ||--o{ reservations : "fulfills"
    pickup_slots ||--o{ reservations : "allocates"
    users ||--o{ reservations : "places"
    guest_customers ||--o{ reservations : "places"
    reservations ||--|{ reservation_items : "contains"
    food_boxes ||--o{ reservation_items : "sold_as"
    reservations ||--o{ payments : "paid_by"
    reservations ||--o{ feedback : "evaluated_by"
    users ||--o{ email_verification_tokens : "receives"
    guest_customers ||--o{ email_verification_tokens : "receives"
    users ||--o{ password_reset_tokens : "requests"
    users ||--o{ admin_activity_logs : "performed_by"
```

---

## 2. Table Descriptions & Business Rules

### `users`
Stores registered customer accounts and administrator users.
- `role`: Distinct role separation (`customer` vs `admin`).
- `password_hash`: Uses PHP's `password_hash()` with `PASSWORD_DEFAULT` (Bcrypt/Argon2i). Never plain text.
- `is_email_verified`: Flags whether customer clicked their activation link.

### `guest_customers`
Enables guests to reserve food without an account.
- Captures verified email and phone number for pickup notifications.
- Referenced by `reservations.guest_id`.

### `bakeries`
Stores partner bakeries in Vietnam with their operating hours, contact details, and locations.
- `district` & `city`: Facilitates neighborhood search for university campuses.

### `food_boxes`
The core surplus offerings.
- `box_type`: Either `snack_box` (40,000 VND) or `meal_box` (70,000 VND).
- `original_value`: Stored to calculate and display consumer savings.
- `available_qty`: Live stock counter guarded by atomic DB transactions (`SELECT ... FOR UPDATE`).

### `pickup_slots`
Daily collection windows per bakery (e.g. 17:30 - 18:30, 19:00 - 20:30).
- `max_capacity` & `reserved_count`: Prevents store congestion during collection.

### `reservations`
Order transactions.
- `reservation_code`: Unique reference (e.g. `NRN-20261105-ABCD`) used for pickup identification and guest lookup.
- `reservation_status`: `Pending`, `Confirmed`, `Collected`, `Cancelled`.
- `payment_status`: Decoupled from reservation status (`Pending`, `Paid`, `Failed`, `Refunded`, `Pay at Pickup`).

### `payments`
Tracks financial transactions.
- Integrates with VNPay and MoMo gateways via transaction references and cryptographic hash verification.
- Supports in-person "Pay at Pickup".

### Security & Token Tables
- `email_verification_tokens`: Time-expiring cryptographic tokens for account activation.
- `phone_verification_tokens`: OTP codes for phone number confirmation.
- `password_reset_tokens`: One-time password reset tokens.
- `admin_activity_logs`: Immutable audit trail of administrative modifications.
