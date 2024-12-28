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
        }}
    >
        {text}
    </button>
);

export default FilterButtons;
