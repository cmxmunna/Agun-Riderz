# Agun Riderzz - Tour Management System

A comprehensive mobile-friendly and fully responsive website for managing club tours and travel expenses for Purbachal Agun Riderzz members. Built with PHP + MySQL.

## 🚀 Features

### User Roles & Permissions

#### Admin / Tour Leader
- **Full Access**: Create/manage tours, add/remove members, add/edit/delete expenses
- **Approval System**: Approve/reject extra bills and expense claims
- **Announcements**: Post and manage club announcements
- **Reports**: View comprehensive reports and analytics
- **Member Management**: Manage all club members and their roles

#### Members
- **Registration**: Register via Facebook or phone number
- **Login**: Multiple login options (email/phone)
- **Tour Management**: View tours, join/leave tours, track participation
- **Expense Tracking**: Add expenses, view cost sharing, track previous records
- **Profile Management**: Update personal information

### Core Features

#### Tour Management
- Create and manage tour details (title, destination, dates, budget)
- Set maximum member limits and tour status
- Join/leave tour functionality
- Tour member tracking and management
- Tour history and upcoming tours display

#### Expense Management
- Add expenses with categories (Fuel, Food, Accommodation, etc.)
- Upload receipt images for expense verification
- Expense approval workflow for admins
- Cost sharing calculations
- Expense history and reporting

#### User Management
- Role-based access control (Admin/Member)
- Facebook integration for registration/login
- Phone number and email authentication
- Profile management with image uploads
- Member directory and search functionality

#### Reporting & Analytics
- Expense reports by tour and member
- Tour participation statistics
- Cost sharing reports
- Member activity tracking
- Export functionality (CSV)

## 🛠️ Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Framework**: Bootstrap 5.3.0
- **Icons**: Font Awesome 6.0.0
- **Server**: Apache/Nginx (XAMPP/WAMP compatible)

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Modern web browser with JavaScript enabled
- Facebook Developer Account (for Facebook integration)

## 🚀 Installation

### 1. Server Setup
```bash
# Clone or download the project to your web server directory
# For XAMPP: C:\xampp\htdocs\Agun-Riderz
# For WAMP: C:\wamp\www\Agun-Riderz
```

### 2. Database Setup
1. Create a new MySQL database named `agun_riderzz`
2. Import the database structure (tables will be created automatically on first run)
3. Update database credentials in `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'agun_riderzz');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 3. File Permissions
```bash
# Create upload directories with write permissions
mkdir uploads
mkdir uploads/receipts
mkdir uploads/profiles
chmod 755 uploads uploads/receipts uploads/profiles
```

### 4. Facebook Integration (Optional)
1. Create a Facebook App at [Facebook Developers](https://developers.facebook.com/)
2. Get your App ID and App Secret
3. Update the Facebook App ID in `login.php`:
```javascript
appId: 'YOUR_FACEBOOK_APP_ID'
```

### 5. Initial Setup
1. Access the website in your browser
2. The system will automatically create the database tables
3. Default admin account will be created:
   - **Email**: admin@agunriderzz.com
   - **Phone**: 01700000000
   - **Password**: admin123

## 📁 Project Structure

```
Agun-Riderz/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── script.js
├── config/
│   └── database.php
├── includes/
│   └── functions.php
├── uploads/
│   ├── receipts/
│   └── profiles/
├── index.php
├── login.php
├── logout.php
├── tours.php
├── expenses.php
├── members.php
├── create_tour.php
├── tour_details.php
├── reports.php
├── profile.php
└── README.md
```

## 🔧 Configuration

### Database Configuration
Edit `config/database.php` to match your database settings:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'agun_riderzz');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### File Upload Settings
The system supports image uploads for:
- Profile pictures (uploads/profiles/)
- Expense receipts (uploads/receipts/)

### Email Configuration (Optional)
To enable email notifications, configure SMTP settings in the functions file.

## 👥 User Guide

### For Admins

#### Creating a Tour
1. Navigate to **Tours** → **Create New Tour**
2. Fill in tour details (title, destination, dates, budget)
3. Set maximum member limit and status
4. Click **Create Tour**

#### Managing Members
1. Go to **Members** page
2. View all registered members
3. Change member roles (Admin/Member)
4. Delete inactive members
5. Export member list

#### Approving Expenses
1. Navigate to **Expenses** page
2. Review pending expenses
3. Click **Approve** or **Reject** buttons
4. View receipt images for verification

#### Creating Reports
1. Access **Reports** section
2. Generate expense reports by tour/date
3. View member participation statistics
4. Export data to CSV format

### For Members

#### Registration
1. Visit the login page
2. Click **Register** tab
3. Fill in personal details
4. Use Facebook login or create password
5. Verify phone number

#### Joining Tours
1. Browse available tours on **Tours** page
2. Click **Join** button on desired tour
3. View tour details and member list
4. Leave tour if needed

#### Adding Expenses
1. Go to **Expenses** page
2. Click **Add Expense**
3. Select tour (optional)
4. Choose category and add details
5. Upload receipt image
6. Submit for approval

## 🔒 Security Features

- **Password Hashing**: All passwords are securely hashed using PHP's password_hash()
- **SQL Injection Protection**: Prepared statements for all database queries
- **XSS Protection**: Input sanitization and output escaping
- **CSRF Protection**: Form tokens and session validation
- **File Upload Security**: File type validation and size limits
- **Role-based Access**: Strict permission checking for all operations

## 📱 Mobile Responsiveness

The website is fully responsive and optimized for:
- Mobile phones (320px+)
- Tablets (768px+)
- Desktop computers (1024px+)

Features include:
- Responsive navigation menu
- Mobile-friendly forms
- Touch-optimized buttons
- Adaptive layouts
- Fast loading times

## 🔧 Customization

### Styling
Edit `assets/css/style.css` to customize:
- Color scheme
- Typography
- Layout spacing
- Component styles

### Functionality
Modify `includes/functions.php` to add:
- New expense categories
- Additional user roles
- Custom validation rules
- Extended reporting features

### Database
Add new tables or modify existing ones for:
- Additional tour information
- Extended member profiles
- Custom expense tracking
- Enhanced reporting

## 🐛 Troubleshooting

### Common Issues

#### Database Connection Error
- Verify database credentials in `config/database.php`
- Ensure MySQL service is running
- Check database name exists

#### File Upload Issues
- Verify upload directory permissions (755)
- Check PHP upload limits in php.ini
- Ensure sufficient disk space

#### Facebook Login Not Working
- Verify Facebook App ID is correct
- Check Facebook App settings
- Ensure domain is added to Facebook App

#### Session Issues
- Check PHP session configuration
- Verify session directory permissions
- Clear browser cookies

### Error Logs
Check your server's error logs for detailed error messages:
- Apache: `/var/log/apache2/error.log`
- XAMPP: `C:\xampp\apache\logs\error.log`

## 📞 Support

For technical support or feature requests:
- Create an issue on the project repository
- Contact the development team
- Check the troubleshooting section above

## 📄 License

This project is developed for Purbachal Agun Riderzz club. All rights reserved.

## 🔄 Updates

### Version 1.0.0
- Initial release
- Complete tour management system
- Expense tracking and approval
- Member management
- Mobile-responsive design
- Facebook integration

### Future Enhancements
- Push notifications
- Advanced reporting with charts
- Mobile app development
- Payment integration
- Tour photo galleries
- Real-time messaging

---

**Developed with ❤️ for Agun Riderzz Community** 