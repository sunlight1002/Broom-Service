import React from 'react';
import { useNavigate } from 'react-router-dom';

const RegularServicesPage = () => {
    const navigate = useNavigate();

    return (
        <div className="page fade-in">
            <h1>Regular Cleaning Services</h1>
            
            <div className="service-description">
                <p>
                    Our regular cleaning services provide comprehensive home maintenance with flexible packages 
                    to suit your needs. We offer 2*, 3*, 4*, and 5* packages with increasing levels of service 
                    to ensure your home maintains the highest standards of cleanliness and organization.
                </p>
            </div>

            <div className="service-features">
                <h3>What's Included in Regular Services</h3>
                <ul>
                    <li>Comprehensive dusting and surface cleaning</li>
                    <li>Kitchen deep cleaning and sanitization</li>
                    <li>Bathroom thorough cleaning and disinfection</li>
                    <li>Floor cleaning and maintenance</li>
                    <li>Window and glass surface cleaning</li>
                    <li>Vacuuming and carpet care</li>
                    <li>Trash removal and recycling</li>
                    <li>Organization and tidying up</li>
                </ul>
            </div>

            <div className="service-packages">
                <h2>Service Packages</h2>
                
                <div className="package-grid">
                    <div className="package-card">
                        <h3>2-Star Package</h3>
                        <p>Basic cleaning service for regular maintenance</p>
                        <ul>
                            <li>General dusting and surface cleaning</li>
                            <li>Basic kitchen and bathroom cleaning</li>
                            <li>Floor vacuuming and mopping</li>
                            <li>Trash removal</li>
                        </ul>
                    </div>

                    <div className="package-card">
                        <h3>3-Star Package</h3>
                        <p>Enhanced cleaning with additional attention to detail</p>
                        <ul>
                            <li>All 2-star services included</li>
                            <li>Deep kitchen cleaning</li>
                            <li>Bathroom sanitization</li>
                            <li>Window cleaning (interior)</li>
                            <li>Basic organization</li>
                        </ul>
                    </div>

                    <div className="package-card">
                        <h3>4-Star Package</h3>
                        <p>Premium cleaning service for luxury homes</p>
                        <ul>
                            <li>All 3-star services included</li>
                            <li>Complete kitchen deep cleaning</li>
                            <li>Full bathroom disinfection</li>
                            <li>Interior and exterior window cleaning</li>
                            <li>Professional organization services</li>
                            <li>Special surface treatment</li>
                        </ul>
                    </div>

                    <div className="package-card">
                        <h3>5-Star Package</h3>
                        <p>Ultimate luxury cleaning experience</p>
                        <ul>
                            <li>All 4-star services included</li>
                            <li>Complete home transformation</li>
                            <li>Premium surface treatments</li>
                            <li>Professional wardrobe organization</li>
                            <li>Specialized equipment cleaning</li>
                            <li>Post-service quality inspection</li>
                            <li>Priority scheduling</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div className="service-benefits">
                <h2>Benefits of Regular Cleaning</h2>
                <div className="benefits-grid">
                    <div className="benefit-item">
                        <h3>🏠 Healthier Environment</h3>
                        <p>Regular cleaning reduces allergens, dust, and bacteria, creating a healthier living space for you and your family.</p>
                    </div>
                    <div className="benefit-item">
                        <h3>⏰ Time Savings</h3>
                        <p>Free up your valuable time to focus on what matters most while we handle the cleaning.</p>
                    </div>
                    <div className="benefit-item">
                        <h3>💎 Property Value</h3>
                        <p>Regular maintenance helps preserve your property's value and appearance over time.</p>
                    </div>
                    <div className="benefit-item">
                        <h3>😌 Peace of Mind</h3>
                        <p>Enjoy a consistently clean and organized home without the stress of managing cleaning tasks.</p>
                    </div>
                </div>
            </div>

            <div className="service-schedule">
                <h2>Flexible Scheduling</h2>
                <p>
                    We offer flexible scheduling options to accommodate your lifestyle:
                </p>
                <ul>
                    <li>Weekly cleaning services</li>
                    <li>Bi-weekly maintenance</li>
                    <li>Monthly deep cleaning</li>
                    <li>Custom schedules based on your needs</li>
                    <li>One-time cleaning services</li>
                </ul>
            </div>

            <div className="service-cta">
                <p>Contact us today to discuss your cleaning needs</p>
                <button className="cta-button primary" onClick={() => navigate('/brochure/contact')}>
                    Contact Us
                </button>
            </div>
        </div>
    );
};

export default RegularServicesPage; 