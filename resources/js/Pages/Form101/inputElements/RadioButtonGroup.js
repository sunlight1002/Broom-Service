export default function RadioButtonGroup({
    name,
    options,
    label,
    required,
    value,
    error,
    onChange,
    onBlur,
    isFlex = false,
    className = 'navyblueColor font-w-500',
    resClassName,
}) {
    
    return (
        <div className="form-group">
            <label className={`control-label d-block ${className}`}>
                {label} {required && "*"}
                <p className="text-danger"
                style={{fontWeight: "normal"}}
                >{error}</p>
            </label>
            <div className={`${isFlex ? "d-flex" : ""} ${resClassName}`}>
                {options.map((option, index) => (
                    <div key={index} className="me-3">
                        <label className="mr-2 radio" htmlFor={name + option.value}>
                            <input
                                type="radio"
                                id={name + option.value}
                                className="mr-1 "
                                name={name}
                                value={option.value}
                                checked={value === option.value}
                                onChange={onChange}
                                onBlur={onBlur}
                            />
                            <span className={`${className}`}>{option.label}</span>
                        </label>
                    </div>
                ))}
            </div>
        </div>
    );
}




// export default function RadioButtonGroup({
//     name,
//     options,
//     label,
//     required,
//     value,
//     error,
//     onChange,
//     onBlur,
//     isFlex = false,
// }) {
//     return (
//         <div className="form-group">
//             <label className="control-label d-block">
//                 {label} {required && "*"}
//                 <p className="text-danger">{error}</p>
//             </label>
//             <div className={isFlex ? "d-flex  " : ""}>
//                 {options.map((option, index) => (
//                     <div key={index} className="me-3">
//                         <label className="mr-2" htmlFor={name + option.value}>
//                             <input
//                                 type="radio"
//                                 id={name + option.value}
//                                 className="mr-1"
//                                 name={name}
//                                 value={option.value}
//                                 checked={value === option.value}
//                                 onChange={onChange}
//                                 onBlur={onBlur}
//                             />
//                             {option.label}
//                         </label>
//                     </div>
//                 ))}
//             </div>
//         </div>
//     );
// }
