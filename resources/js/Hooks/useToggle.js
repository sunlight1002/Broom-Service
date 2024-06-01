import { useState } from "react";

export default function useToggle(defaultValue) {
    const [toggle, setToggle] = useState(defaultValue);

    function toggleValue(value) {
        setToggle((currentValue) =>
            typeof value === "boolean" ? value : !currentValue
        );
    }

    return [toggle, toggleValue];
}
