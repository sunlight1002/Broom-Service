import React from "react";
import { useTranslation } from "react-i18next";

const ChangeYear = () => {
    const { t } = useTranslation();
    return (
        <div>
            <h2>{t("form101.year_changes_details1")}</h2>
            <div className="bg-yellow rounded p-2">
                {t("form101.year_changes_details1")}
            </div>
        </div>
    );
};

export default ChangeYear;
