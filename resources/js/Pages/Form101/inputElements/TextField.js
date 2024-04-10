import React from "react";

export default function TextField({
    name,
    type = "text",
    label,
    value,
    onChange,
    onBlur,
    error,
    required,
}) {
    return (
        <div className="text-start form-group">
            <label htmlFor={name} className="control-label">
                {label} {required && "*"}
            </label>
            <br />
            <input
                className="form-control man"
                type={type}
                name={name}
                id={name}
                value={value}
                onChange={onChange}
                onBlur={onBlur}
            />
            <p className="text-danger">{error}</p>
        </div>
    );
}
