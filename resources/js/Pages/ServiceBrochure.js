import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import './ServiceBrochure.css';

// Import individual page components
import HomePage from './ServiceBrochurePages/HomePage';
import ServicesPage from './ServiceBrochurePages/ServicesPage';
import AboutPage from './ServiceBrochurePages/AboutPage';
import ContactPage from './ServiceBrochurePages/ContactPage';
import QRCodePage from './ServiceBrochurePages/QRCodePage';

const ServiceBrochure = () => {
    const { page } = useParams();
    const navigate = useNavigate();
    const [isMenuOpen, setIsMenuOpen] = useState(false);

    useEffect(() => {
        if (page === undefined) {
            navigate('/brochure/home', { replace: true });
        }
    }, [page, navigate]);

    const handleNavigation = (pageName) => {
        navigate(`/brochure/${pageName}`);
        setIsMenuOpen(false);
    };

    const renderPage = () => {
        switch (page) {
            case 'home':
                return <HomePage />;
            case 'services':
                return <ServicesPage />;
            case 'about':
                return <AboutPage />;
            case 'contact':
                return <ContactPage />;
            case 'qr':
                return <QRCodePage />;
            default:
                return <HomePage />;
        }
    };

    return (
        <div className="brochure-container">
            <header className="brochure-header">
                <div className="header-content">
                    <div className="logo-section">
                        <img src="/images/logo.png" alt="Broom Service" className="logo" />
                        <h1 className="company-name hide-on-mobile">Broom Service</h1>
                    </div>
                    <nav className="desktop-nav">
                        <ul className="nav-menu">
                            <li><button className={page === 'home' ? 'active' : ''} onClick={() => handleNavigation('home')}>Home</button></li>
                            <li><button className={page === 'services' ? 'active' : ''} onClick={() => handleNavigation('services')}>Services</button></li>
                            <li><button className={page === 'about' ? 'active' : ''} onClick={() => handleNavigation('about')}>About</button></li>
                            <li><button className={page === 'contact' ? 'active' : ''} onClick={() => handleNavigation('contact')}>Contact</button></li>
                            <li><button className={page === 'qr' ? 'active' : ''} onClick={() => handleNavigation('qr')}>QR Codes</button></li>
                        </ul>
                    </nav>
                    <button 
                        className={`menu-toggle${isMenuOpen ? ' open' : ''}`}
                        onClick={() => setIsMenuOpen(!isMenuOpen)}
                        aria-label="Toggle menu"
                    >
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </div>
            </header>

            {/* Drawer for mobile nav */}
            <div className={`mobile-drawer${isMenuOpen ? ' open' : ''}`}
                onClick={() => setIsMenuOpen(false)}
                >
                <nav className="drawer-nav" onClick={e => e.stopPropagation()}>
                    <div className="drawer-header">
                        <img src="/images/logo.png" alt="Broom Service" className="drawer-logo" />
                        <button className="drawer-close" onClick={() => setIsMenuOpen(false)} aria-label="Close menu">&times;</button>
                    </div>
                    <div className="drawer-menu">
                        <button className={page === 'home' ? 'active' : ''} onClick={() => handleNavigation('home')}>Home</button>
                        <button className={page === 'services' ? 'active' : ''} onClick={() => handleNavigation('services')}>Services</button>
                        <button className={page === 'about' ? 'active' : ''} onClick={() => handleNavigation('about')}>About</button>
                        <button className={page === 'contact' ? 'active' : ''} onClick={() => handleNavigation('contact')}>Contact</button>
                        <button className={page === 'qr' ? 'active' : ''} onClick={() => handleNavigation('qr')}>QR Codes</button>
                    </div>
                </nav>
            </div>

            <main className="brochure-main">
                {renderPage()}
            </main>

            <footer className="brochure-footer">
                <div className="footer-content">
                    <div className="footer-section">
                        <h3>Broom Service</h3>
                        <p>Professional cleaning services since 2015</p>
                        <p>License Number 4569</p>
                    </div>
                    <div className="footer-section">
                        <h4>Contact Information</h4>
                        <p>📧 office@broomservice.co.il</p>
                        <p>📞 +972-XX-XXXXXXX</p>
                        <p>🌐 www.broomservice.co.il</p>
                    </div>
                    <div className="footer-section">
                        <h4>Service Areas</h4>
                        <p>Tel Aviv, Jerusalem, Haifa, and surrounding areas</p>
                    </div>
                </div>
                <div className="footer-bottom">
                    <p>&copy; 2025 Broom Service. All rights reserved.</p>
                </div>
            </footer>
        </div>
    );
};

export default ServiceBrochure; 