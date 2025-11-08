# TerraTrade - Land Trading System

A comprehensive PHP-based land trading platform for the Philippine real estate market, featuring user portals, due diligence tools, offer workflows, auctions, KYC compliance, digital signatures, and escrow services.

## 🏠 Features

### Core Functionality
- **User Management**: Role-based access (buyers, sellers, brokers, admins)
- **Property Listings**: Comprehensive property management with images and documents
- **Search & Filters**: Advanced property search with location, price, and zoning filters
- **Favorites**: Save and manage favorite properties

### Trading Features
- **Offer System**: Make offers with contingencies and terms
- **Counter Offers**: Negotiate with automated counter-offer workflow
- **Auction System**: Timed auctions with automatic bid extensions
- **Escrow Services**: Secure payment handling with milestone releases

### Compliance & Security
- **KYC/AML**: Document verification and compliance checking
- **Digital Signatures**: Integrated e-signature capabilities
- **Audit Logging**: Comprehensive activity tracking
- **Data Privacy**: RA 10173 compliant data handling

### Communication
- **Messaging System**: Real-time communication between parties
- **Notifications**: Email and in-app notification system
- **Document Sharing**: Secure file sharing and management

### Administration
- **Admin Dashboard**: Complete system oversight and management
- **User Moderation**: Account approval and suspension tools
- **Dispute Resolution**: Built-in dispute handling system
- **Reporting**: Comprehensive analytics and reporting

## 🚀 Installation

### Requirements
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled

### Quick Install
1. Clone or download the project to your web server directory
2. Navigate to `http://yoursite.com/install/install.php`
3. Follow the installation wizard:
   - Configure database connection
   - Create database and tables
   - Set up admin user
   - Complete installation

### Manual Installation
1. Import the database schema from `database/schema.sql`
2. Copy `config/config.php.example` to `config/config.php` and configure
3. Set up your web server to point to the project directory
4. Ensure the `uploads/` directory is writable

## 📁 Project Structure

```
TerraTrade/
├── api/                    # API endpoints
│   └── index.php          # API router
├── config/                 # Configuration files
│   ├── config.php         # Main configuration
│   └── database.php       # Database connection
├── controllers/            # MVC Controllers
│   ├── AuthController.php
│   ├── PropertyController.php
│   ├── OfferController.php
│   ├── UserController.php
│   ├── MessageController.php
│   ├── AdminController.php
│   ├── KYCController.php
│   ├── AuctionController.php
│   └── EscrowController.php
├── database/              # Database files
│   └── schema.sql         # Database schema
├── includes/              # Helper files
│   ├── functions.php      # Common functions
│   └── auth.php          # Authentication helpers
├── install/               # Installation wizard
│   └── install.php       # Installation script
├── js/                    # JavaScript files
│   └── app.js            # Main application JS
├── uploads/               # File uploads (created during install)
├── index.php             # Main application entry point
├── styles.css            # Application styles
└── .htaccess             # Apache configuration
```

## 🔧 Configuration

### Database Configuration
Update `config/database.php` with your database credentials:

```php
private $host = 'localhost';
private $database = 'terratrade_db';
private $username = 'your_username';
private $password = 'your_password';
```

### Email Configuration
Configure SMTP settings in `config/config.php`:

```php
define('SMTP_HOST', 'your-smtp-host');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@domain.com');
define('SMTP_PASSWORD', 'your-password');
```

### Security Settings
- Update `JWT_SECRET` in `config/config.php`
- Configure SSL certificate for production
- Set appropriate file permissions (755 for directories, 644 for files)

## 🎯 Usage

### For Buyers
1. Register and complete KYC verification
2. Browse properties or use advanced search
3. Save favorites and contact sellers
4. Make offers with contingencies
5. Participate in auctions
6. Manage contracts and escrow

### For Sellers
1. Register and list properties
2. Upload property documents and images
3. Receive and manage offers
4. Create auctions for competitive bidding
5. Handle contracts and payments

### For Admins
1. Access admin dashboard
2. Review and approve property listings
3. Manage user accounts and KYC submissions
4. Handle disputes and moderation
5. Monitor system activity and generate reports

## 🔐 Security Features

- **CSRF Protection**: All forms protected against CSRF attacks
- **SQL Injection Prevention**: Prepared statements throughout
- **XSS Protection**: Input sanitization and output encoding
- **File Upload Security**: Restricted file types and secure storage
- **Session Management**: Secure session handling
- **Audit Logging**: Complete activity tracking

## 📊 Database Schema

The system includes comprehensive database tables:

- **Users & Authentication**: users, user_sessions, kyc_documents
- **Properties**: properties, property_images, property_documents
- **Trading**: offers, counter_offers, auction_bids, contracts
- **Communication**: conversations, conversation_participants, messages
- **Financial**: escrow_accounts, escrow_transactions
- **Administration**: notifications, audit_logs, system_settings, disputes

## 🌐 API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user

### Properties
- `GET /api/properties/list` - Get properties with filters
- `GET /api/properties/{id}` - Get property details
- `POST /api/properties/create` - Create new property
- `PUT /api/properties/{id}` - Update property
- `POST /api/properties/favorite/{id}` - Toggle favorite

### Offers
- `POST /api/offers/create` - Create new offer
- `GET /api/offers/list` - Get user offers
- `POST /api/offers/respond/{id}` - Accept/reject offer
- `POST /api/offers/counter/{id}` - Create counter offer

### Additional endpoints available for auctions, escrow, messaging, KYC, and admin functions.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:
- Check the documentation
- Review the code comments
- Create an issue on the repository

## 🚧 Development Status

This is a fully functional land trading system with all core features implemented:

✅ **Completed Features:**
- User authentication and role management
- Property listing and management
- Offer and counter-offer system
- Auction system with timed bidding
- Escrow and payment handling
- KYC/AML compliance system
- Messaging and notifications
- Admin dashboard and moderation
- Audit logging and reporting

🔄 **Future Enhancements:**
- Digital signature integration
- Mobile app development
- Advanced analytics dashboard
- Integration with external mapping services
- Automated property valuation

## 📞 Contact

For business inquiries or custom development needs, please contact the development team.

---

**TerraTrade** - Empowering secure and transparent land trading in the Philippines.
