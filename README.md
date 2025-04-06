# 🏢 Foodbank Management System

## **Overview**
The **Foodbank Management System** is a Laravel + Vue 3 application designed to facilitate the donation, distribution, and tracking of food donations across multiple foodbanks. The system includes user authentication, role-based dashboards, donation management, recipient tracking, and real-time notifications.

---

## **🚀 Features**
✅ **User Authentication & Authorization**  
- JWT-based authentication  
- Role-based access control using CASL Vue (Admin, Foodbanks, Donors, Recipients)  

✅ **Foodbank Management**  
- Admin can register, approve, or reject foodbanks  
- Foodbanks can manage donation requests  

✅ **Donation Management**  
- Donors can create and track donations  
- The system matches donations to foodbank requests  
- Admin approves or rejects donations  

✅ **Recipient Management**  
- Recipients can sign up and request donations  
- Recipients provide feedback and receive SMS notifications  

✅ **Reporting & Analytics**  
- Admins and foodbanks can generate reports using ApexCharts  
- Real-time donation trends and recipient demographics  

✅ **Real-Time Notifications**  
- Laravel Echo and Pusher for instant donation updates  

---

## **📊 Data Flow Overview**
### **1️⃣ User Authentication & Authorization**
- Users log in using email/password or Google OAuth.
- Laravel issues a JWT token for secure authentication.
- CASL Vue manages access based on roles.

### **2️⃣ Foodbank Registration & Approval**
- Admin registers foodbanks via a form.
- Approval or rejection notifications are sent to the foodbanks.

### **3️⃣ Donation Lifecycle**
1. Donors create a donation request (food type, quantity, recipient).
2. The system matches donations to foodbank requests.
3. Admin reviews and approves donations.
4. Donors and recipients receive real-time notifications.

### **4️⃣ Real-Time Notifications**
- Events like donation approval trigger Laravel Echo/Pusher.
- Users get instant notifications about updates.

---

## **📝 User Stories**
### **👨‍💼 Admin User Stories**
- ✅ As an Admin, I want to register foodbanks so they can receive donations.
- ✅ As an Admin, I want to monitor donation statuses and generate reports.

### **🏢 Foodbank User Stories**
- ✅ As a Foodbank, I want to create donation requests to receive needed food.
- ✅ As a Foodbank, I want to track assigned donations.

### **🎁 Donor User Stories**
- ✅ As a Donor, I want to donate food by specifying type and quantity.
- ✅ As a Donor, I want to track my donation history.

### **👥 Recipient User Stories**
- ✅ As a Recipient, I want to sign up and request food.
- ✅ As a Recipient, I want to submit feedback on received donations.

---

## **🛠️ Tech Stack**
| Stack | Technology Used |
|----------------|----------------|
| **Frontend** | Vue 3, Vuex, Tailwind CSS |
| **Backend** | Laravel 10, Sanctum (JWT Authentication) |
| **Database** | MySQL |
| **Notifications** | Laravel Echo, Pusher |
| **Messaging** | Twilio/Nexmo for SMS |
| **Charts** | Chart.js, ApexCharts |
| **Hosting** | Railway.app, Vercel |

---

## **🔧 Installation & Setup**
### **1️⃣ Clone the Repository**
```bash
git clone https://github.com/YOUR_USERNAME/foodbank-management-system.git
cd foodbank-management-system
