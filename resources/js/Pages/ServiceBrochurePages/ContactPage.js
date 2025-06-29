import React from 'react';

const ContactPage = () => {
    return (
        <div className="contact-page">
            <div className="contact-hero">
                <div className="container">
                    <h1>Contact Us</h1>
                    <p>Get in touch for professional cleaning services</p>
                </div>
            </div>

            <div className="contact-content">
                <div className="container">
                    <div className="contact-info-section">
                        <h2>Get In Touch</h2>
                        <div className="contact-details-grid">
                            <div className="contact-detail-card">
                                <div className="contact-icon">📧</div>
                                <div>
                                    <h4>Email</h4>
                                    <p>office@broomservice.co.il</p>
                                </div>
                            </div>
                            <div className="contact-detail-card">
                                <div className="contact-icon">📞</div>
                                <div>
                                    <h4>Phone</h4>
                                    <p>+972-XX-XXXXXXX</p>
                                </div>
                            </div>
                            <div className="contact-detail-card">
                                <div className="contact-icon">🌐</div>
                                <div>
                                    <h4>Website</h4>
                                    <p>www.broomservice.co.il</p>
                                </div>
                            </div>
                            <div className="contact-detail-card">
                                <div className="contact-icon">🏢</div>
                                <div>
                                    <h4>License</h4>
                                    <p>License Number 4569</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="service-areas">
                        <h3>Service Areas</h3>
                        <p className='mb-2'>We provide professional cleaning services in:</p>
                        <div className="areas-grid">
                            <div className="area-item">
                                <span className="area-icon">🏙️</span>
                                <span>Tel Aviv</span>
                            </div>
                            <div className="area-item">
                                <span className="area-icon">⛪</span>
                                <span>Jerusalem</span>
                            </div>
                            <div className="area-item">
                                <span className="area-icon">🌊</span>
                                <span>Haifa</span>
                            </div>
                            <div className="area-item">
                                <span className="area-icon">🏘️</span>
                                <span>Surrounding Areas</span>
                            </div>
                        </div>
                    </div>

                    <div className="service-areas" style={{ marginBlock: '20px' }}>
                        <h3>Business Hours</h3>
                        <div className="hours-grid">
                            <div className="area-item">
                                <span className="area-icon">📅</span>
                                <span>Monday - Friday</span>
                                <span className="time">8:00 AM - 6:00 PM</span>
                            </div>
                            <div className="area-item">
                                <span className="area-icon">📅</span>
                                <span>Saturday</span>
                                <span className="time">9:00 AM - 4:00 PM</span>
                            </div>
                            <div className="area-item">
                                <span className="area-icon">❌</span>
                                <span>Sunday</span>
                                <span className="time">Closed</span>
                            </div>
                        </div>
                        <p className="emergency-note">
                            * Emergency cleaning services available outside business hours
                        </p>
                    </div>

                    <div className="why-contact">
                        <h2>Why Contact Broom Service?</h2>
                        <div className="reasons-grid">
                            <div className="reason-item">
                                <span className="reason-icon">💬</span>
                                <h4>Free Consultation</h4>
                                <p>Get personalized advice and recommendations for your cleaning needs</p>
                            </div>
                            <div className="reason-item">
                                <span className="reason-icon">💰</span>
                                <h4>Competitive Pricing</h4>
                                <p>Transparent pricing with no hidden fees or additional charges</p>
                            </div>
                            <div className="reason-item">
                                <span className="reason-icon">⚡</span>
                                <h4>Quick Response</h4>
                                <p>Fast response times and flexible scheduling options</p>
                            </div>
                            <div className="reason-item">
                                <span className="reason-icon">🛡️</span>
                                <h4>Licensed & Insured</h4>
                                <p>Peace of mind with our licensed and fully insured services</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ContactPage; 