import React from 'react';
import { useNavigate } from 'react-router-dom';

const HighPressurePage = () => {
    const navigate = useNavigate();

    return (
        <div className="page fade-in">
            <h1>High Pressure Cleaning Services</h1>
            
            <div className="service-description">
                <p>
                    Our high-pressure cleaning services provide thorough and professional cleaning for various 
                    surfaces. Suitable for regular or one-time service, our high-pressure cleaning is highly 
                    effective for removing tough stains and restoring surfaces to their original condition.
                </p>
            </div>

            <div className="service-features">
                <h3>High Pressure Cleaning Applications</h3>
                <ul>
                    <li>Floor cleaning and stain removal</li>
                    <li>Wall cleaning and stubborn stain removal</li>
                    <li>External door cleaning for fresh appearance</li>
                    <li>Sunshade cleaning to restore cleanliness and shine</li>
                    <li>Driveway and walkway cleaning</li>
                    <li>Deck and patio cleaning</li>
                    <li>Fence and railing cleaning</li>
                    <li>Outdoor furniture cleaning</li>
                </ul>
            </div>

            <div className="cleaning-areas">
                <h2>Areas We Clean</h2>
                <div className="areas-grid">
                    <div className="area-item">
                        <h3>🏠 Residential Areas</h3>
                        <p>Homes, apartments, and residential properties</p>
                    </div>
                    <div className="area-item">
                        <h3>🏢 Commercial Properties</h3>
                        <p>Office buildings, retail spaces, and commercial facilities</p>
                    </div>
                    <div className="area-item">
                        <h3>🏭 Industrial Sites</h3>
                        <p>Factories, warehouses, and industrial facilities</p>
                    </div>
                    <div className="area-item">
                        <h3>🏗️ Construction Sites</h3>
                        <p>Post-construction cleanup and surface preparation</p>
                    </div>
                </div>
            </div>

            <div className="equipment">
                <h2>Professional Equipment</h2>
                <p>
                    We use state-of-the-art high-pressure cleaning equipment that can handle various 
                    pressure levels and surface types, ensuring optimal results without damage.
                </p>
                <ul>
                    <li>Commercial-grade pressure washers</li>
                    <li>Adjustable pressure settings</li>
                    <li>Various nozzle attachments</li>
                    <li>Eco-friendly cleaning solutions</li>
                    <li>Safety equipment and protective gear</li>
                </ul>
            </div>

            <div className="benefits">
                <h2>Benefits of High Pressure Cleaning</h2>
                <div className="benefits-grid">
                    <div className="benefit-item">
                        <h3>🧹 Deep Cleaning</h3>
                        <p>Removes deeply embedded dirt and stains that regular cleaning can't reach</p>
                    </div>
                    <div className="benefit-item">
                        <h3>⏰ Time Efficient</h3>
                        <p>Faster and more effective than manual cleaning methods</p>
                    </div>
                    <div className="benefit-item">
                        <h3>💰 Cost Effective</h3>
                        <p>Prevents the need for expensive surface replacement</p>
                    </div>
                    <div className="benefit-item">
                        <h3>🌱 Eco-Friendly</h3>
                        <p>Uses less water and chemicals than traditional cleaning methods</p>
                    </div>
                </div>
            </div>

            <div className="service-cta">
                <p>Contact us today to discuss your high pressure cleaning needs</p>
                <button className="cta-button primary" onClick={() => navigate('/brochure/contact')}>
                    Contact Us
                </button>
            </div>
        </div>
    );
};

export default HighPressurePage; 