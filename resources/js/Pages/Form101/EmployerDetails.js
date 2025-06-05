//EmployerDetails

import React from "react";
import TextField from "./inputElements/TextField";
import { useTranslation } from "react-i18next";

export default function EmployerDetails({
    errors,
    values,
    touched,
    handleBlur,
    handleChange,
    handleBubbleToggle,
    activeBubble,
    show = true 
}) {
    const { t } = useTranslation();

    if (!show) return null;
    
    return (
        <div>
            <p className="navyblueColor font-24 font-w-500 mt-3 mb-2">{t("form101.employer_details")}</p>
            <div className="row">
                <div className="col-sm">
                    <TextField
                        name="employerName"
                        label={t("form101.label_name")}
                        value={values.employerName}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        readonly={true}
                        error={
                            touched.employerName && errors.employerName
                                ? errors.employerName
                                : ""
                        }
                    />
                </div>
                <div className="col-sm">
                    <TextField
                        name="employerAddress"
                        label={t("form101.label_address")}
                        value={values.employerAddress}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        readonly={true}
                        error={
                            touched.employerAddress && errors.employerAddress
                                ? errors.employerAddress
                                : ""
                        }
                    />
                </div>
            </div>
            <div className="row">
                <div className="col-sm">
                    <TextField
                        name="employerPhone"
                        label={t("form101.label_phNum")}
                        value={values.employerPhone}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        readonly={true}
                        error={
                            touched.employerPhone && errors.employerPhone
                                ? errors.employerPhone
                                : ""
                        }
                    />
                </div>
                <div className="col-sm">
                    <TextField
                        name="employerDeductionsFileNo"
                        label={t("form101.label_ddfileId")}
                        value={values.employerDeductionsFileNo}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        readonly={true}
                        error={
                            touched.employerDeductionsFileNo &&
                                errors.employerDeductionsFileNo
                                ? errors.employerDeductionsFileNo
                                : ""
                        }
                    />
                </div>
            </div>
        </div>
    );
}
