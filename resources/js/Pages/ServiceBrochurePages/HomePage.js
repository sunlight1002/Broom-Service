import React from 'react';
import { useNavigate } from 'react-router-dom';

const HomePage = () => {
    const navigate = useNavigate();

    const handleServiceClick = (pageName) => {
        navigate(`/brochure/${pageName}`);
    };

    return (
        <div className="landing-page">
            {/* Hero Section */}
            <section className="hero-section">
                <div className="hero-content">
                    <div className="hero-text">
                        <h1 className="hero-title">Professional Cleaning Services</h1>
                        <p className="hero-subtitle">
                            Premium cleaning solutions for homes and businesses since 2015. 
                            Licensed, insured, and committed to excellence.
                        </p>
                        <div className="hero-features">
                            <span>🏆 Licensed & Insured</span>
                            <span>👥 Professional Team</span>
                            <span>⭐ 5-Star Service</span>
                            <span>💯 Satisfaction Guaranteed</span>
                        </div>
                        <div className="hero-buttons">
                            <button className="cta-button primary" onClick={() => handleServiceClick('services')}>
                                Our Services
                            </button>
                            <button className="cta-button secondary" onClick={() => handleServiceClick('contact')}>
                                Contact Us
                            </button>
                        </div>
                    </div>
                    <div className="hero-image">
                        <img src="/images/main-banner.jpg" alt="Professional Cleaning Service" />
                    </div>
                </div>
            </section>

            {/* Services Preview Section */}
            <section className="services-preview">
                <div className="container">
                    <h2>Our Premium Services</h2>
                    <p className="section-subtitle">Comprehensive cleaning solutions tailored to your needs</p>
                    
                    <div className="services-grid">
                        <div className="service-card" onClick={() => handleServiceClick('services')}>
                            <div className="service-image">
                                <img src="/images/senior.jpg" alt="Home Cleaning" />
                            </div>
                            <div className="service-content">
                                <h3>Home Cleaning</h3>
                                <p>Regular and deep cleaning services for your home</p>
                                <span className="service-badge">2★ - 5★ Packages</span>
                            </div>
                        </div>
                        
                        <div className="service-card" onClick={() => handleServiceClick('services')}>
                            <div className="service-image">
                                <img src="/images/senior.jpg" alt="Office Cleaning" />
                            </div>
                            <div className="service-content">
                                <h3>Office Cleaning</h3>
                                <p>Professional workplace cleaning for enhanced productivity</p>
                                <span className="service-badge">Commercial</span>
                            </div>
                        </div>
                        
                        <div className="service-card" onClick={() => handleServiceClick('services')}>
                            <div className="service-image">
                                <img src="/images/senior.jpg" alt="Post-Renovation Cleaning" />
                            </div>
                            <div className="service-content">
                                <h3>Post-Renovation</h3>
                                <p>Thorough cleaning after construction or renovation work</p>
                                <span className="service-badge">Deep Clean</span>
                            </div>
                        </div>
                        
                        <div className="service-card" onClick={() => handleServiceClick('services')}>
                            <div className="service-image">
                                <img src="/images/senior.jpg" alt="Specialized Cleaning" />
                            </div>
                            <div className="service-content">
                                <h3>Specialized Services</h3>
                                <p>Window cleaning, organization, and specialized cleaning</p>
                                <span className="service-badge">Premium</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* Why Choose Us Section */}
            <section className="why-choose-us">
                <div className="container">
                    <div className="why-content">
                        <div className="why-text">
                            <h2>Why Choose Broom Service?</h2>
                            <p>
                                We are a luxury cleaning company, approved by the Ministry of Economy, 
                                with experience since 2015 in transforming homes and luxury apartments 
                                into pampering places at the level of a five-star hotel.
                            </p>
                            <div className="benefits-list">
                                <div className="benefit-item">
                                    <span className="benefit-icon">👥</span>
                                    <div>
                                        <h4>Professional Team</h4>
                                        <p>Permanent, skilled workers with thorough training</p>
                                    </div>
                                </div>
                                <div className="benefit-item">
                                    <span className="benefit-icon">🛡️</span>
                                    <div>
                                        <h4>Licensed & Insured</h4>
                                        <p>License Number 4569, fully insured for your peace of mind</p>
                                    </div>
                                </div>
                                <div className="benefit-item">
                                    <span className="benefit-icon">⭐</span>
                                    <div>
                                        <h4>5-Star Quality</h4>
                                        <p>Hotel-level service standards for your home</p>
                                    </div>
                                </div>
                                <div className="benefit-item">
                                    <span className="benefit-icon">💯</span>
                                    <div>
                                        <h4>Satisfaction Guaranteed</h4>
                                        <p>Payment only after you're completely satisfied</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="why-image">
                            <img src="/images/senior.jpg" alt="Professional Cleaning Team" />
                        </div>
                    </div>
                </div>
            </section>

            {/* Stats Section */}
            <section className="stats-section">
                <div className="container">
                    <div className="stats-grid">
                        <div className="stat-item">
                            <h3>9+</h3>
                            <p>Years of Experience</p>
                        </div>
                        <div className="stat-item">
                            <h3>1000+</h3>
                            <p>Happy Clients</p>
                        </div>
                        <div className="stat-item">
                            <h3>50+</h3>
                            <p>Professional Staff</p>
                        </div>
                        <div className="stat-item">
                            <h3>100%</h3>
                            <p>Satisfaction Rate</p>
                        </div>
                    </div>
                </div>
            </section>

            {/* CTA Section */}
            <section className="cta-section">
                <div className="container">
                    <h2>Ready to Experience Premium Cleaning?</h2>
                    <p>Contact us today to discuss your cleaning needs</p>
                    <div className="cta-buttons">
                        <button className="cta-button primary" onClick={() => handleServiceClick('contact')}>
                            Contact Us
                        </button>
                        <button className="cta-button secondary" onClick={() => handleServiceClick('about')}>
                            Learn More
                        </button>
                    </div>
                </div>
            </section>
        </div>
    );
};

export default HomePage; 