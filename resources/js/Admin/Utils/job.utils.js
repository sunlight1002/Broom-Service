export const getWorkerBasedOnProperty = (worker, address) => {
    const tmpWorker = worker.filter((w) => {
        return (
            (address.prefer_type !== "default" && address.prefer_type !== "both"
                ? w.gender === address.prefer_type
                : true) &&
            (Boolean(address.is_cat_avail)
                ? Boolean(w.is_afraid_by_cat)
                    ? false
                    : !Boolean(w.is_afraid_by_cat)
                : true) &&
            (Boolean(address.is_dog_avail)
                ? Boolean(w.is_afraid_by_dog)
                    ? false
                    : !Boolean(w.is_afraid_by_dog)
                : true)
        );
    });
    return tmpWorker;
};
