import React from "react";

export default function DateField({
    name,
    label,
    value,
    onChange,
    onBlur,
    error,
    required,
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
                className="form-control"
                name={name}
                id={name}
                value={value}
                onChange={onChange}
                onBlur={onBlur}
                readOnly={readOnly}
            />
            <p className="text-danger">{error}</p>
        </div>
    );
}
