import React from 'react';

const HostingServicesPage = () => {
    return (
        <div className="page fade-in">
            <h1>Hosting Services</h1>
            
            <div className="service-description">
                <p>
                    If you are hosting at your home, we have the perfect solution for you. We would be happy
                    to help you become guests in your own home. Our comprehensive hosting services ensure that
                    you can focus on enjoying your guests while we handle all the preparation and cleanup.
                </p>
            </div>

            <div className="service-features">
                <h3>Hosting Services Include</h3>
                <ul>
                    <li>Basic or thorough cleaning before, after, or during the hosting at your home</li>
                    <li>Professional waiters for serving your guests</li>
                    <li>Assistance in cutting and preparing ingredients</li>
                    <li>Kitchen organization and preparation</li>
                    <li>Table setting and decoration</li>
                    <li>Post-event cleanup and restoration</li>
                </ul>
            </div>

            <div className="hosting-packages">
                <h2>Hosting Service Packages</h2>
                
                <div className="package-grid">
                    <div className="package-card">
                        <h3>Basic Hosting Support</h3>
                        <p>Essential cleaning and preparation services</p>
                        <ul>
                            <li>Pre-event home cleaning</li>
                            <li>Kitchen preparation assistance</li>
                            <li>Basic table setting</li>
                            <li>Post-event cleanup</li>
                        </ul>
                    </div>

                    <div className="package-card">
                        <h3>Standard Hosting Package</h3>
                        <p>Comprehensive hosting support with waiter service</p>
                        <ul>
                            <li>All basic services included</li>
                            <li>Professional waiter service</li>
                            <li>Advanced kitchen preparation</li>
                            <li>Table decoration and setup</li>
                            <li>Guest service during event</li>
                        </ul>
                    </div>

                    <div className="package-card">
                        <h3>Premium Hosting Experience</h3>
                        <p>Complete hosting solution for special occasions</p>
                        <ul>
                            <li>All standard services included</li>
                            <li>Multiple professional waiters</li>
                            <li>Complete ingredient preparation</li>
                            <li>Premium table decoration</li>
                            <li>Full event coordination</li>
                            <li>Post-event deep cleaning</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div className="service-benefits">
                <h2>Benefits of Our Hosting Services</h2>
                <div className="benefits-grid">
                    <div className="benefit-item">
                        <h3>😌 Stress-Free Hosting</h3>
                        <p>Focus on enjoying your guests while we handle all the work behind the scenes.</p>
                    </div>
                    <div className="benefit-item">
                        <h3>👨‍🍳 Professional Service</h3>
                        <p>Experienced staff who know how to make your event special and memorable.</p>
                    </div>
                    <div className="benefit-item">
                        <h3>🏠 Home Comfort</h3>
                        <p>Host in the comfort of your own home with professional support.</p>
                    </div>
                    <div className="benefit-item">
                        <h3>✨ Perfect Presentation</h3>
                        <p>Ensure your home and food presentation is impeccable for your guests.</p>
                    </div>
                </div>
            </div>

            <div className="event-types">
                <h2>Perfect for Various Events</h2>
                <p>Our hosting services are ideal for:</p>
                <div className="event-grid">
                    <div className="event-item">
                        <h3>🎉 Family Gatherings</h3>
                        <p>Make family celebrations special and stress-free</p>
                    </div>
                    <div className="event-item">
                        <h3>💼 Business Dinners</h3>
                        <p>Professional hosting for important business meetings</p>
                    </div>
                    <div className="event-item">
                        <h3>🎂 Birthday Parties</h3>
                        <p>Celebrate special birthdays with style</p>
                    </div>
                    <div className="event-item">
                        <h3>💍 Special Occasions</h3>
                        <p>Anniversaries, engagements, and other celebrations</p>
                    </div>
                </div>
            </div>

            <div className="planning-process">
                <h2>Planning Your Hosting Event</h2>
                <div className="process-steps">
                    <div className="step">
                        <h3>1. Consultation</h3>
                        <p>We discuss your event details and requirements</p>
                    </div>
                    <div className="step">
                        <h3>2. Customization</h3>
                        <p>We tailor our services to your specific needs</p>
                    </div>
                    <div className="step">
                        <h3>3. Preparation</h3>
                        <p>Our team prepares your home and assists with setup</p>
                    </div>
                    <div className="step">
                        <h3>4. Event Support</h3>
                        <p>Professional service throughout your event</p>
                    </div>
                    <div className="step">
                        <h3>5. Cleanup</h3>
                        <p>Complete post-event cleanup and restoration</p>
                    </div>
                </div>
            </div>

            <div className="cta-section">
                <h2>Ready to Host with Confidence?</h2>
                <p>Contact us to plan your perfect hosting experience</p>
                <button className="cta-button" onClick={() => window.location.href = 'mailto:office@broomservice.co.il'}>
                    Plan Your Event
                </button>
            </div>
        </div>
    );
};

export default HostingServicesPage; 