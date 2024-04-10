import React from "react";

export default function CheckBox({
    name,
    label,
    value = "",
    checked,
    onChange,
    onBlur,
    error,
    required,
    disabled = false,
}) {
    return (
        <div className="text-start">
            <label>
                <input
                    type="checkbox"
                    className="mr-2"
                    name={name}
                    disabled={disabled}
                    value={value}
                    checked={checked}
                    onChange={onChange}
                    onBlur={onBlur}
                />
                {label} {required && "*"}
            </label>
            <br />
            {error && <p className="text-danger">{error}</p>}
        </div>
    );
}
