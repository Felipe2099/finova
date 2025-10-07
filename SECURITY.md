# Security Policy

## 🔒 Reporting a Vulnerability

We take the security of Finova seriously. If you discover a security vulnerability, please follow these steps:

### Reporting Process

1. **DO NOT** open a public GitHub issue for security vulnerabilities
2. Email us directly at: **hi@mikpa.com**
3. Include detailed information about the vulnerability:
   - Description of the vulnerability
   - Steps to reproduce
   - Potential impact
   - Suggested fix (if any)

### What to Expect

- **Response Time:** We aim to respond within 24 hours
- **Updates:** We'll keep you informed about the progress
- **Credit:** We'll acknowledge your contribution (if desired) once the issue is resolved

## 🛡️ Security Best Practices

### For Deployment

1. **Environment Variables**
   - Never commit `.env` files
   - Use strong, unique passwords

2. **Database Security**
   - Use strong database passwords
   - Enable SSL/TLS connections

3. **API Keys**
   - Keep OpenAI/Gemini API keys secure
   - Use environment variables
   - Monitor API usage regularly

4. **Server Configuration**
   - Keep PHP and Laravel updated
   - Use HTTPS only
   - Enable firewall rules
   - Regular security updates

### For Development

1. **Dependencies**
   - Run `composer audit` regularly
   - Keep dependencies updated
   - Review package permissions

2. **Code Quality**
   - Enable PHPStan static analysis
   - Follow security best practices
   - Sanitize user inputs
   - Use parameterized queries

## 🔄 Supported Versions

We provide security updates for the following versions:

| Version | Supported          |
| ------- | ------------------ |
| 1.x.x   | :white_check_mark: |
| < 1.0   | :x:                |

## 📋 Security Checklist for Self-Hosting

- [ ] Change default admin credentials immediately
- [ ] Configure proper file permissions (755 for directories, 644 for files)
- [ ] Disable debug mode in production (`APP_DEBUG=false`)
- [ ] Set up regular backups
- [ ] Enable HTTPS with valid SSL certificate
- [ ] Configure proper CORS settings
- [ ] Set up rate limiting
- [ ] Enable database encryption
- [ ] Review and configure `.htaccess` or nginx rules
- [ ] Set up monitoring and logging

## 🚨 Known Security Considerations

### Sensitive Data Storage

- Customer credentials are encrypted using Laravel's encryption
- API keys should be stored in environment variables only
- Database backups should be encrypted

### AI Integration

- OpenAI and Gemini API keys must be kept secure
- Monitor API usage to prevent abuse
- Consider implementing usage limits per user

### File Uploads

- Validate file types and sizes
- Scan uploaded files for malware
- Store files outside web root when possible

## 📞 Contact

For security-related questions or concerns:
- **Email:** security@mikpa.com
- **PGP Key:** Available upon request

---

**Thank you for helping keep Finova secure!** 🙏

