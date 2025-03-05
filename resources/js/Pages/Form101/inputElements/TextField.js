
import React from "react";
import { AiOutlineQuestionCircle } from "react-icons/ai";

export default function TextField({
    name,
    type = "text",
    label,
    value,
    onChange,
    onBlur,
    error,
    required,
    readonly,
    toggleBubble = false, // Passed from parent to handle toggling
    id = name,
    disabled = false
}) {
    return (
        <div className="text-start form-group">
            <label htmlFor={name} className="control-label font-w-500 navyblueColor">
                {label} {required && "*"}
            </label>
            <br />
            <div className="d-flex align-items-center">
                <input
                    className={`form-control man  ${error ? 'is-invalid' : ''}`}
                    type={type}
                    name={name}
                    id={id}
                    value={value}
                    onChange={onChange}
                    onBlur={onBlur}
                    readOnly={readonly}
                    style={{
                        border: !error && "2px solid #E9ECF2",
                        background: "inherit"
                    }}
                    disabled={disabled}
                />
                {toggleBubble && (
                    <span
                        className="d-flex justify-content-center align-items-center p-2 font-20"
                        style={{
                            backgroundColor: "rgb(244 246 249)",
                            marginLeft: "10px",
                            borderRadius: "10px",
                            cursor: "pointer"
                        }}
                        onClick={() => toggleBubble(name)} // Trigger toggle when clicked
                    >
                        <AiOutlineQuestionCircle />
                    </span>
                )}
            </div>
            <p className="text-danger">{error}</p>
        </div>
    );
}


// import React from "react";

// export default function TextField({
//     name,
//     type = "text",
//     label,
//     value,
//     onChange,
//     onBlur,
//     error,
//     required,
//     readonly,
// }) {
//     return (
//         <div className="text-start form-group">
//             <label htmlFor={name} className="control-label">
//                 {label} {required && "*"}
//             </label>
//             <br />
//             <input
//                 className="form-control man"
//                 type={type}
//                 name={name}
//                 id={name}
//                 value={value}
//                 onChange={onChange}
//                 onBlur={onBlur}
//                 readOnly={readonly}
//             />
//             <p className="text-danger">{error}</p>
//         </div>
//     );
// }
