import React from "react";
import TextField from "./inputElements/TextField";
import { useTranslation } from "react-i18next";

export default function EmployerDetails({
    errors,
    values,
    touched,
    handleBlur,
    handleChange,
}) {
    const { t } = useTranslation();
    return (
        <div>
            <h2>{t("form101.employer_details")}</h2>
            <div className="row">
                <div className=" col-md-3 col-sm-6 col-xs-6">
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
                <div className="col-md-3 col-sm-6 col-xs-6">
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
                <div className="col-md-3 col-sm-6 col-xs-6">
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
                <div className="col-md-3 col-sm-6 col-xs-6">
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
