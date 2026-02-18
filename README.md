# 🏥 Healthcare Appointment System

## 📌 Overview

The Healthcare Appointment System is a web-based platform designed to reduce hospital queues and improve patient experience by enabling online appointment booking.

Patients can check doctor availability, book appointments in advance, receive automated notifications, and manage their bookings efficiently.  
The system streamlines hospital operations and minimizes waiting time through structured scheduling and digital communication.

---

## 🎯 Problem Statement

Traditional hospital systems often require patients to physically visit hospitals to check doctor availability, resulting in:

- Long queues
- Poor time management
- Overcrowding
- Inefficient communication

This system addresses those challenges by providing a centralized digital appointment platform.

---

## 🚀 Key Features

### 👤 Patient Features
- Register & Login (JWT Authentication)
- View doctor availability
- Book appointments based on available time slots
- View appointment history
- Secure online payments via Stripe
- Receive SMS notifications (Twilio integration)

### 👨‍⚕️ Doctor Features
- Manage availability schedule
- View assigned appointments
- Update appointment status

### 🛠 Admin Features
- Manage users (patients & doctors)
- Monitor appointments
- Generate reports
- Role-based access control

---

## 🛠️ Tech Stack

### 🔹 Frontend
- HTML5
- JavaScript (Vanilla JS)

### 🔹 Backend
- PHP
- JSON (API communication)
- JWT (Authentication & Authorization)

### 🔹 Database
- MySQL (Relational data management)

### 🔹 Integrations
- Stripe (Online payments)
- Twilio (SMS notifications)
- Calendar Integration (Appointment scheduling & reminders)

---

## 🏗️ System Architecture

The application follows a modular architecture:

- Frontend communicates with backend via REST-style endpoints.
- Authentication handled using JWT tokens.
- Role-based middleware protects sensitive routes.
- MySQL manages relational data (users, roles, appointments, payments).
- Third-party APIs handle payments and messaging.

---

## 📂 Core Functional Modules

- User Authentication & Authorization
- Appointment Booking Engine
- Doctor Availability Management
- Payment Processing System
- SMS Notification System
- Reporting & Dashboard Analytics

---

## 🔐 Security Measures

- JWT-based authentication
- Password hashing
- Role-based access control
- Secure payment processing via Stripe
- Input validation and sanitization

---

## ⚙️ Installation Guide

### 1️⃣ Clone the Repository

```bash
git clone https://github.com/yvesmugisha901/healthcare-appointment-system.git
