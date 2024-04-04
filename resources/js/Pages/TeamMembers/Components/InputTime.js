const InputTime = () => {
    return (
        <select name="start" className="form-control">
            <option value="">Start Time</option>
            {slots.map((t, i) => {
                return (
                    <option value={t} key={i}>
                        {" "}
                        {t}{" "}
                    </option>
                );
            })}
        </select>
    );
};

export default InputTime;
