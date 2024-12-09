import React from "react";

export default function DateField({
    name,
    label,
    value,
    onChange,
    onBlur,
    error,
    required,
    id = name,
    readOnly,
}) {
    return (
        <div className="form-group">
            <label htmlFor={name} className="control-label">
                {label} {required && "*"}
            </label>
            <br />
            <input
                type="date"
                className={`form-control ${error ? 'is-invalid' : ''}`}
                name={name}
                id={id}
                value={value}
                onChange={onChange}
                onBlur={onBlur}
                readOnly={readOnly}
            />
            <p className="text-danger">{error}</p>
        </div>
    );
}
