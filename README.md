# AI Enhanced Note Editor

A modern, intelligent note-taking application built with Laravel and React that leverages AI to enhance your writing experience.

![AI Enhanced Note Editor](https://img.shields.io/badge/Laravel-12.0-red?style=flat-square&logo=laravel)
![React](https://img.shields.io/badge/React-19.0-blue?style=flat-square&logo=react)
![TypeScript](https://img.shields.io/badge/TypeScript-5.7-blue?style=flat-square&logo=typescript)
![OpenAI](https://img.shields.io/badge/OpenAI-API-green?style=flat-square&logo=openai)

## 🚀 Features

### Core Functionality

- **📝 Rich Note Editor**: Create and edit notes with a clean, intuitive interface
- **🏷️ Smart Tagging**: Organize notes with customizable tags
- **🔍 Advanced Search**: Find notes by title, content, or tags
- **💾 Auto-Save**: Automatic saving every 2 seconds while editing
- **📱 Responsive Design**: Works seamlessly on desktop and mobile devices

### AI-Powered Enhancements

- **✨ AI Summarization**: Generate concise summaries of your notes
- **📝 Writing Improvement**: Enhance clarity, grammar, and style
- **🏷️ Auto Tag Generation**: AI-powered tag suggestions
- **🔄 Real-time Streaming**: Live AI responses with streaming technology

### Authentication & Security

- **🔐 Google OAuth**: Secure login with Google accounts
- **🛡️ HTTPS Support**: SSL/TLS encryption for secure connections
- **🔒 Session Management**: Secure user session handling

### Analytics & Insights

- **📊 Raw PHP Analytics**: Comprehensive note statistics and insights
- **📈 Trend Analysis**: Monthly note creation patterns
- **☁️ Word Cloud**: Visual representation of most common words
- **🎯 Tag Analytics**: Most frequently used tags with charts

### Technical Features

- **🐳 Docker Support**: Containerized deployment
- **🗄️ Multi-Database**: PostgreSQL, MySQL, SQLite support
- **⚡ Modern Stack**: Laravel 12 + React 19 + TypeScript
- **🎨 Beautiful UI**: Tailwind CSS with Radix UI components

## 🛠️ Technology Stack

### Backend

- **Laravel 12.0** - PHP framework
- **PHP 8.2+** - Server-side language
- **Inertia.js** - Modern monolith architecture
- **Laravel Socialite** - OAuth authentication

### Frontend

- **React 19** - UI library
- **TypeScript 5.7** - Type-safe JavaScript
- **Tailwind CSS 4.0** - Utility-first CSS framework
- **Radix UI** - Accessible component primitives
- **Vite 6.0** - Fast build tool

### AI Integration

- **OpenAI API** - GPT models for content enhancement
- **Streaming Responses** - Real-time AI interactions

### Database

- **PostgreSQL**

## 📋 Prerequisites

Before you begin, ensure you have the following installed:

- **PHP 8.2 or higher**
- **Composer** (PHP dependency manager)
- **Node.js 18+ and npm** (for frontend dependencies)
- **Docker & Docker Compose** (for containerized deployment)
- **PostgreSQL/MySQL** (or use AWS RDS)

## 🚀 How to Run This Project

### Method 1: Local Development

#### 1. Clone the Repository

```bash
git clone <your-repository-url>
cd ai-enhanced-note-editor
```

#### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node.js dependencies
npm install
```

#### 3. Environment Setup

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

#### 4. Configure Environment Variables

Edit `.env` file with your settings:

```env
APP_NAME="AI Enhanced Note Editor"
APP_ENV=local
APP_KEY=base64:your-generated-key
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ai_note_editor
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Google OAuth (see Google Cloud Console setup below)
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URL=http://localhost:8000/auth/google/callback

# OpenAI Configuration
OPENAI_API_KEY=sk-your-openai-api-key
OPENAI_MODEL=gpt-4o-mini
```

#### 5. Run Development Servers

```bash
# Start Laravel and Vite development servers
composer run dev

# Or run separately:
# Terminal 1: Laravel server
php artisan serve

# Terminal 2: Vite dev server
npm run dev
```

#### 6. Access the Application

- **Main App**: http://localhost:8000
- **Analytics**: http://localhost:8000/analytics

### Method 2: Docker Deployment (Production)

#### 1. Build and Run with Docker

```bash
# Build the Docker image
docker build -t ai-note-editor .

# Run the container
docker run -d \
  --name ai-note-editor \
  -p 80:80 \
  -p 443:443 \
  --env-file .env \
  ai-note-editor
```

#### 2. With SSL (Recommended for Production)

```bash
# Generate SSL certificate (Let's Encrypt)
sudo certbot certonly --standalone -d yourdomain.com

# Run with SSL support
docker run -d \
  --name ai-note-editor \
  -p 80:80 \
  -p 443:443 \
  -v /etc/letsencrypt:/etc/letsencrypt:ro \
  --env-file .env \
  ai-note-editor
```

## 🗄️ Database Setup

### Local Database Setup

#### PostgreSQL (Recommended)

```bash
# Install PostgreSQL
sudo apt install postgresql postgresql-contrib

# Create database and user
sudo -u postgres psql
CREATE DATABASE ai_note_editor;
CREATE USER your_username WITH PASSWORD 'your_password';
GRANT ALL PRIVILEGES ON DATABASE ai_note_editor TO your_username;
\q
```

#### MySQL Alternative

```bash
# Install MySQL
sudo apt install mysql-server

# Create database
mysql -u root -p
CREATE DATABASE ai_note_editor;
CREATE USER 'your_username'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON ai_note_editor.* TO 'your_username'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### AWS RDS Setup (Production)

1. **Create RDS Instance**:

    - Go to AWS RDS Console
    - Choose PostgreSQL or MySQL
    - Configure instance settings
    - Note the endpoint, username, and password

2. **Update Environment Variables**:

    ```env
    DB_HOST=your-rds-endpoint.amazonaws.com
    DB_PORT=5432
    DB_DATABASE=your_database_name
    DB_USERNAME=your_db_username
    DB_PASSWORD=your_db_password
    ```

3. **Configure Security Group**:
    - Allow inbound connections on port 5432 (PostgreSQL) or 3306 (MySQL)
    - From your EC2 instance's security group

## 🔄 Database Migration

### Run Migrations

```bash
# Run database migrations
php artisan migrate

# Or in Docker container
docker exec ai-note-editor php artisan migrate --force

# Run with sample data (optional)
php artisan db:seed
```

### Migration Commands

```bash
# Check migration status
php artisan migrate:status

# Rollback migrations (if needed)
php artisan migrate:rollback

# Fresh migration (drops all tables)
php artisan migrate:fresh

# Fresh migration with seeding
php artisan migrate:fresh --seed
```

## 🔐 Google Cloud Console Setup

### 1. Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable the Google+ API

### 2. Configure OAuth Consent Screen

1. Go to **APIs & Services** → **OAuth consent screen**
2. Choose **External** user type
3. Fill in required information:
    - App name: "AI Enhanced Note Editor"
    - User support email: your-email@example.com
    - Developer contact: your-email@example.com
4. Add scopes: `email`, `profile`, `openid`
5. Add test users (if in testing mode)

### 3. Create OAuth 2.0 Credentials

1. Go to **APIs & Services** → **Credentials**
2. Click **Create Credentials** → **OAuth 2.0 Client IDs**
3. Choose **Web application**
4. Configure URLs:

#### For Local Development:
Authorized JavaScript origins:
http://localhost:8000

Authorized redirect URIs:
http://localhost:8000/auth/google/callback


#### For Production (with domain):
Authorized JavaScript origins:
https://yourdomain.com

Authorized redirect URIs:
https://yourdomain.com/auth/google/callback


#### For Production (with IP):
Use a dynamic DNS service like No-IP:

Authorized JavaScript origins:
https://yourapp.ddns.net

Authorized redirect URIs:
https://yourapp.ddns.net/auth/google/callback


### 4. Copy Credentials
1. Copy **Client ID** and **Client Secret**
2. Add them to your `.env` file:
   ```env
   GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
   GOOGLE_CLIENT_SECRET=your-client-secret
   ```

### 5. Publish Your App (Optional)
- For production, publish your OAuth consent screen
- This removes the "unverified app" warning

## 🚀 Deployment Guide

### AWS EC2 Deployment

#### 1. Launch EC2 Instance
- Choose Ubuntu 22.04 LTS
- Configure security groups (ports 22, 80, 443)
- Create or use existing key pair

#### 2. Install Dependencies
```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh
sudo usermod -aG docker ubuntu

# Install Certbot for SSL
sudo apt install certbot
```

#### 3. Setup Domain (Optional)
- Use services like No-IP for free subdomains
- Configure DNS to point to your EC2 IP

#### 4. Deploy Application
```bash
# Clone repository
git clone <your-repo-url>
cd ai-enhanced-note-editor

# Configure environment
cp .env.example .env
nano .env  # Edit with your settings

# Generate SSL certificate
sudo certbot certonly --standalone -d yourdomain.com

# Build and run
docker build -t ai-note-editor .
docker run -d \
  --name ai-note-editor \
  -p 80:80 \
  -p 443:443 \
  -v /etc/letsencrypt:/etc/letsencrypt:ro \
  --env-file .env \
  ai-note-editor

# Run migrations
docker exec ai-note-editor php artisan migrate --force
```

## 📊 Analytics Dashboard

The application includes a comprehensive analytics dashboard built with raw PHP:

### Features
- **📈 Note Statistics**: Total notes, word count, average length
- **📅 Monthly Trends**: Note creation patterns over time
- **🏷️ Tag Analytics**: Most frequently used tags
- **☁️ Word Cloud**: Common words visualization
- **📱 Responsive Charts**: Interactive visualizations

### Access
- Navigate to `/analytics` in your application
- Requires authentication
- Works with all supported databases

## 🔧 Configuration

### Environment Variables
```env
# Application
APP_NAME="AI Enhanced Note Editor"
APP_ENV=production
APP_KEY=base64:your-key
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=your-db-host
DB_PORT=5432
DB_DATABASE=your-database
DB_USERNAME=your-username
DB_PASSWORD=your-password

# Authentication
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URL=https://yourdomain.com/auth/google/callback

# AI Integration
OPENAI_API_KEY=sk-your-openai-key
OPENAI_MODEL=gpt-4o-mini

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120

# Cache
CACHE_STORE=database
```

### Common Issues

#### OAuth Issues
- Ensure Google Cloud Console URLs match exactly
- Check that OAuth consent screen is configured
- Verify SSL certificates for HTTPS

#### AI Features Not Working
- Verify OpenAI API key is valid
- Check API usage limits
- Ensure model name is correct

#### Docker Issues
```bash
# Check container logs
docker logs ai-note-editor

# Restart container
docker restart ai-note-editor

# Rebuild image
docker build -t ai-note-editor . --no-cache
```
