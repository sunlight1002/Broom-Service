import React from 'react';
import { useNavigate } from 'react-router-dom';

const GrillCleaningPage = () => {
    const navigate = useNavigate();

    return (
        <div className="page fade-in">
            <h1>Professional Grill & Oven Cleaning</h1>
            
            <div className="service-description">
                <p>
                    Broom Service specializes in cleaning gas grills, outdoor kitchens, and ovens, providing
                    thorough and professional service right to your doorstep. With extensive experience and a
                    multitude of satisfied customers, the company guarantees a high-level service experience,
                    ensuring correct and professional work.
                </p>
                <p>
                    Broom Service's premium cleaning ensures the longevity of your grill, enhances your cooking
                    experience, and guarantees excellent quality and taste for your dishes. The company also
                    offers special deals and benefits for regular customers.
                </p>
            </div>

            <div className="service-features">
                <h3>Cleaning Process Includes</h3>
                <ul>
                    <li>Removal of tough grease and buildup</li>
                    <li>Thorough cleaning of grates and radiants</li>
                    <li>Use of natural cleaning agents for proper and comprehensive cleaning</li>
                    <li>Deep cleaning of all grill components</li>
                    <li>Oven interior and exterior cleaning</li>
                    <li>Outdoor kitchen surface cleaning</li>
                    <li>Safety inspection and maintenance check</li>
                </ul>
            </div>

            <div className="equipment-types">
                <h2>Equipment We Clean</h2>
                <div className="equipment-grid">
                    <div className="equipment-item">
                        <h3>🔥 Gas Grills</h3>
                        <p>Professional cleaning of all gas grill types and models</p>
                    </div>
                    <div className="equipment-item">
                        <h3>🏠 Outdoor Kitchens</h3>
                        <p>Complete outdoor kitchen cleaning and maintenance</p>
                    </div>
                    <div className="equipment-item">
                        <h3>🍳 Ovens</h3>
                        <p>Deep cleaning of conventional and convection ovens</p>
                    </div>
                    <div className="equipment-item">
                        <h3>🔥 Charcoal Grills</h3>
                        <p>Thorough cleaning of charcoal grills and smokers</p>
                    </div>
                </div>
            </div>

            <div className="cleaning-benefits">
                <h2>Benefits of Professional Cleaning</h2>
                <div className="benefits-grid">
                    <div className="benefit-item">
                        <h3>🔧 Equipment Longevity</h3>
                        <p>Regular cleaning extends the life of your grill and oven equipment</p>
                    </div>
                    <div className="benefit-item">
                        <h3>🍖 Better Cooking</h3>
                        <p>Clean equipment ensures better heat distribution and cooking results</p>
                    </div>
                    <div className="benefit-item">
                        <h3>🛡️ Safety</h3>
                        <p>Prevents grease fires and ensures safe operation</p>
                    </div>
                    <div className="benefit-item">
                        <h3>✨ Hygiene</h3>
                        <p>Eliminates bacteria and ensures food safety</p>
                    </div>
                </div>
            </div>

            <div className="cleaning-process">
                <h2>Our Professional Cleaning Process</h2>
                <div className="process-steps">
                    <div className="step">
                        <h3>1. Assessment</h3>
                        <p>We evaluate the condition and type of your equipment</p>
                    </div>
                    <div className="step">
                        <h3>2. Disassembly</h3>
                        <p>Carefully disassemble components for thorough cleaning</p>
                    </div>
                    <div className="step">
                        <h3>3. Deep Cleaning</h3>
                        <p>Use professional tools and eco-friendly solutions</p>
                    </div>
                    <div className="step">
                        <h3>4. Reassembly</h3>
                        <p>Properly reassemble and test all components</p>
                    </div>
                    <div className="step">
                        <h3>5. Inspection</h3>
                        <p>Final inspection and safety check</p>
                    </div>
                </div>
            </div>

            <div className="maintenance-tips">
                <h2>Maintenance Tips</h2>
                <p>To keep your equipment in top condition between professional cleanings:</p>
                <ul>
                    <li>Clean grates after each use</li>
                    <li>Remove excess grease regularly</li>
                    <li>Check for gas leaks periodically</li>
                    <li>Cover equipment when not in use</li>
                    <li>Schedule regular professional cleanings</li>
                </ul>
            </div>

            <div className="service-cta">
                <p>Contact us today to discuss your grill and oven cleaning needs</p>
                <button className="cta-button primary" onClick={() => navigate('/brochure/contact')}>
                    Contact Us
                </button>
            </div>
        </div>
    );
};

export default GrillCleaningPage; 