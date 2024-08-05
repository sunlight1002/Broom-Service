import React from 'react';
import './loader.css';

const Loader = () => {
    return (
        <div className="skeleton-wrapper">
            <div className="skeleton skeleton-header"></div>
            <div className="skeleton skeleton-paragraph"></div>
            <div className="skeleton skeleton-paragraph"></div>
            <div className="skeleton skeleton-paragraph short"></div>
        </div>
    );
};

export default Loader;
