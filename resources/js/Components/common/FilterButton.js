

const handleLocalStorage = (name) => {
    console.log("Setting localStorage:", name);

    const validNames = ["Day", "Week", "Month"];
    const validTypes = ["Previous", "Next", "Current"];
    if (validNames.includes(name)) {
        localStorage.setItem("selectedDateRange", name);
        console.log("Stored:", localStorage.getItem("selectedDateRange"));  // ✅ Check if value is stored
    }
    if(validTypes.includes(name)){
        localStorage.setItem("selectedDateStep", name);
        console.log("Stored:", localStorage.getItem("selectedDateStep"));  // ✅ Check if value is stored
    }

};



const FilterButtons = ({
    text,
    name = text,
    className,
    selectedFilter,
    setselectedFilter,
    onClick,
}) => (
    
    <button
        className={`btn border rounded ${className}`}
        style={
            selectedFilter === name
                ? { background: "white" }
                : {
                      background: "#2c3f51",
                      color: "white",
                  }
        }
        onClick={() => {
            onClick?.();
            setselectedFilter(name);
            handleLocalStorage(name);
        }}
    >
        {text}
    </button>
);

export default FilterButtons;
