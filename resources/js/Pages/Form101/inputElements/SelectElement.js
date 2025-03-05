import React from "react";

export default function SelectElement({
    name,
    label,
    value,
    onChange,
    options,
    onBlur,
    error,
    required,
    disabled = false
}) {
    return (
        <div className="form-group">
            <label className="control-label">
                {label}
                {required && "*"}
            </label>
            <br />
            <select
                name={name}
                value={value}
                onChange={onChange}
                onBlur={onBlur}
                disabled={disabled}
                className="form-control pid"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            <p className="text-danger">{error}</p>
        </div>
    );
}
