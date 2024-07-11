import React from "react";
import TextField from "./inputElements/TextField";
import SelectElement from "./inputElements/SelectElement";
import DateField from "./inputElements/DateField";
import RadioButtonGroup from "./inputElements/RadioButtonGroup";
import { useTranslation } from "react-i18next";
import { countryOption } from "./cityCountry";

export default function EmployeeDetails({
    errors,
    values,
    touched,
    handleBlur,
    handleChange,
    setFieldValue,
}) {
    const { t } = useTranslation();

    const sexOptions = [
        { label: t("form101.label_male"), value: "Male" },
        { label: t("form101.label_female"), value: "Female" },
    ];

    const maritalStatusOptions = [
        { label: t("form101.status_single"), value: "Single" },
        { label: t("form101.status_married"), value: "Married" },
        { label: t("form101.status_divorcee"), value: "Divorced" },
        { label: t("form101.status_widower"), value: "Widowed" },
        { label: t("form101.status_separated"), value: "Separated" },
    ];

    const isIsraeliResidentOptions = [
        { label: t("form101.label_yes"), value: "Yes" },
        { label: t("form101.label_no"), value: "No" },
    ];

    const isKibbutzMemberOptions = [
        { label: t("form101.label_yes"), value: "Yes" },
        { label: t("form101.label_no"), value: "No" },
    ];

    const isHealthFundMemberOptions = [
        { label: t("form101.label_yes"), value: "Yes" },
        { label: t("form101.label_no"), value: "No" },
    ];
    const HealthFundname = [
        { label: "Clalit", value: "Clalit" },
        { label: "Maccabi", value: "Maccabi" },
        { label: "Meuhedet", value: "Meuhedet" },
        { label: "Leumit", value: "Leumit" },
    ];
    return (
        <div>
            <h2>{t("form101.employee_details")}</h2>
            <div className="row">
                <div className="col-sm-6 col-xs-6">
                    <TextField
                        name="employeeFirstName"
                        label={t("form101.label_firstName")}
                        value={values.employeeFirstName}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        readonly={true}
                        error={
                            touched.employeeFirstName &&
                            errors.employeeFirstName
                                ? errors.employeeFirstName
                                : ""
                        }
                        required
                    />
                </div>{" "}
                <div className="col-sm-6 col-xs-6">
                    <TextField
                        name="employeeLastName"
                        label={t("form101.label_lastName")}
                        value={values.employeeLastName}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        readonly={true}
                        error={
                            touched.employeeLastName && errors.employeeLastName
                                ? errors.employeeLastName
                                : ""
                        }
                        required
                    />
                </div>
                <div className="col-12">
                    <label className="control-label d-block">
                        {t("form101.idBy")} *
                    </label>
                    <label>
                        <input
                            type="radio"
                            name="employeeIdentityType"
                            value="IDNumber"
                            className="mr-2"
                            checked={values.employeeIdentityType === "IDNumber"}
                            onChange={handleChange}
                        />
                        {t("form101.id_num")}
                    </label>
                    <label>
                        <input
                            type="radio"
                            name="employeeIdentityType"
                            value="Passport"
                            className="mr-2"
                            checked={values.employeeIdentityType === "Passport"}
                            onChange={handleChange}
                        />
                        {t("form101.passport_foreign")}
                    </label>
                    {touched.employeeIdentityType &&
                        errors.employeeIdentityType && (
                            <p className="text-danger">
                                {errors.employeeIdentityType}
                            </p>
                        )}
                </div>
                {values.employeeIdentityType === "Passport" ? (
                    <>
                        <div className="col-md-4 col-sm-6 col-xs-6">
                            <SelectElement
                                name={"employeecountry"}
                                label={t("form101.country_passport")}
                                value={values.employeecountry}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.employeecountry &&
                                    errors.employeecountry
                                        ? errors.employeecountry
                                        : ""
                                }
                                options={countryOption}
                            />
                        </div>
                        <div className="col-sm-4 col-xs-6">
                            <div>
                                <TextField
                                    name="employeePassportNumber"
                                    label={t("form101.passport_num")}
                                    value={values.employeecountry !== "Israel" ? values.employeePassportNumber : ""}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    error={
                                        touched.employeePassportNumber &&
                                        errors.employeePassportNumber
                                            ? errors.employeePassportNumber
                                            : ""
                                    }
                                    required
                                />
                            </div>
                            <div className="col-md-4 col-sm-6 col-xs-6">
                                <label htmlFor="employeepassportCopy">
                                    {t("form101.passport_photo")}
                                </label>
                                <br />
                                <input
                                    type="file"
                                    name="employeepassportCopy"
                                    id="employeepassportCopy"
                                    accept="image/*"
                                    onChange={(e) =>
                                        setFieldValue(
                                            "employeepassportCopy",
                                            e.target.files[0]
                                        )
                                    }
                                    onBlur={handleBlur}
                                />
                                {touched.employeepassportCopy &&
                                    errors.employeepassportCopy && (
                                        <p className="text-danger">
                                            {errors.employeepassportCopy}
                                        </p>
                                    )}
                            </div>
                        </div>
                        <div className="col-md-4 col-sm-6 col-xs-6">
                            <label htmlFor="employeeResidencePermit">
                                {t("form101.PhotoCopyResident")}
                            </label>
                            <br />
                            <input
                                type="file"
                                name="employeeResidencePermit"
                                id="employeeResidencePermit"
                                accept="image/*"
                                onChange={(e) =>
                                    setFieldValue(
                                        "employeeResidencePermit",
                                        e.target.files[0]
                                    )
                                }
                                onBlur={handleBlur}
                            />
                            {touched.employeeResidencePermit &&
                                errors.employeeResidencePermit && (
                                    <p className="text-danger">
                                        {errors.employeeResidencePermit}
                                    </p>
                                )}
                        </div>
                    </>
                ) : (
                    <>
                        <div className="col-md-4 col-sm-6 col-xs-6">
                            <TextField
                                name="employeeIdNumber"
                                label={t("form101.id_num")}
                                value={values.employeecountry === "Israel" ? values.employeeIdNumber : ""}
                                onChange={handleChange}
                                onBlur={handleBlur}
                                error={
                                    touched.employeeIdNumber &&
                                    errors.employeeIdNumber
                                        ? errors.employeeIdNumber
                                        : ""
                                }
                                required
                            />
                        </div>
                        <div className="col-sm-4 col-xs-6">
                            <label htmlFor="employeeIdCardCopy">
                                {t("form101.id_photocopy")}
                            </label>
                            <br />
                            <input
                                type="file"
                                name="employeeIdCardCopy"
                                id="employeeIdCardCopy"
                                accept="image/*"
                                onChange={(e) =>
                                    setFieldValue(
                                        "employeeIdCardCopy",
                                        e.target.files[0]
                                    )
                                }
                                onBlur={handleBlur}
                            />
                            {touched.employeeIdCardCopy &&
                                errors.employeeIdCardCopy && (
                                    <p className="text-danger">
                                        {errors.employeeIdCardCopy}
                                    </p>
                                )}
                        </div>
                    </>
                )}
            </div>
            <div className="row">
                <div className="col-sm-6 col-xs-6">
                    <DateField
                        name="employeeDob"
                        label={t("form101.dob")}
                        value={values.employeeDob}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        error={
                            touched.employeeDob && errors.employeeDob
                                ? errors.employeeDob
                                : ""
                        }
                        required
                    />
                </div>
                <div className="col-sm-6 col-xs-6">
                    {values.employeeIdentityType === "IDNumber" && (
                        <DateField
                            name="employeeDateOfAliyah"
                            label={t("form101.dom")}
                            value={values.employeeDateOfAliyah}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={
                                touched.employeeDateOfAliyah &&
                                errors.employeeDateOfAliyah
                                    ? errors.employeeDateOfAliyah
                                    : ""
                            }
                        />
                    )}
                </div>
                <div className="col-md-3 col-sm-6 col-xs-6">
                    <TextField
                        name="employeeCity"
                        label={t("form101.City")}
                        value={values.employeeCity}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        error={
                            touched.employeeCity && errors.employeeCity
                                ? errors.employeeCity
                                : ""
                        }
                        required
                    />
                </div>
                <div className="col-md-3 col-sm-6 col-xs-6">
                    <TextField
                        name="employeeStreet"
                        label={t("form101.street")}
                        value={values.employeeStreet}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        error={
                            touched.employeeStreet && errors.employeeStreet
                                ? errors.employeeStreet
                                : ""
                        }
                        required
                    />
                </div>
                <div className="col-md-3 col-sm-6 col-xs-6">
                    <TextField
                        name="employeeHouseNo"
                        label={t("form101.ho_num")}
                        value={values.employeeHouseNo}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        error={
                            touched.employeeHouseNo && errors.employeeHouseNo
                                ? errors.employeeHouseNo
                                : ""
                        }
                        required
                    />
                </div>
                <div className="col-md-3 col-sm-6 col-xs-6">
                    <TextField
                        name="employeePostalCode"
                        label={t("form101.postal_code")}
                        value={values.employeePostalCode}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        error={
                            touched.employeePostalCode &&
                            errors.employeePostalCode
                                ? errors.employeePostalCode
                                : ""
                        }
                        required
                    />
                </div>
                <div className="col-md-6">
                    <TextField
                        name="employeeMobileNo"
                        label={t("form101.mob_num")}
                        value={values.employeeMobileNo}
                        onChange={handleChange}
                        readonly={true}
                        onBlur={handleBlur}
                        error={
                            touched.employeeMobileNo && errors.employeeMobileNo
                                ? errors.employeeMobileNo
                                : ""
                        }
                        required
                    />
                </div>
                <div className="col-md-6">
                    <TextField
                        name="employeePhoneNo"
                        label={t("form101.label_phNum")}
                        value={values.employeePhoneNo}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        error={
                            touched.employeePhoneNo && errors.employeePhoneNo
                                ? errors.employeePhoneNo
                                : ""
                        }
                    />
                </div>
                <div className="col-12">
                    <TextField
                        name="employeeEmail"
                        label={t("form101.label_email")}
                        value={values.employeeEmail}
                        onChange={handleChange}
                        readonly={true}
                        onBlur={handleBlur}
                        error={
                            touched.employeeEmail && errors.employeeEmail
                                ? errors.employeeEmail
                                : ""
                        }
                        required={true}
                    />
                </div>
                <div className="col-lg-2 col-sm-4 col-xs-6">
                    <RadioButtonGroup
                        name="employeeSex"
                        label={t("form101.label_sex")}
                        options={sexOptions}
                        value={values.employeeSex}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        error={
                            touched.employeeSex && errors.employeeSex
                                ? errors.employeeSex
                                : ""
                        }
                        required
                    />
                </div>
                <div className="col-lg-2 col-sm-4 col-xs-6">
                    <RadioButtonGroup
                        name="employeeMaritalStatus"
                        label={t("form101.martial_status")}
                        options={maritalStatusOptions}
                        value={values.employeeMaritalStatus}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        error={
                            touched.employeeMaritalStatus &&
                            errors.employeeMaritalStatus
                                ? errors.employeeMaritalStatus
                                : ""
                        }
                        required
                    />
                </div>
                <div className="col-lg-2 col-sm-4 col-xs-6">
                    <RadioButtonGroup
                        name="employeeIsraeliResident"
                        label={t("form101.israeli_resident")}
                        options={isIsraeliResidentOptions}
                        value={values.employeeIsraeliResident}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        error={
                            touched.employeeIsraeliResident &&
                            errors.employeeIsraeliResident
                                ? errors.employeeIsraeliResident
                                : ""
                        }
                        required
                    />
                </div>
                <div className="col-lg-3 col-sm-4 col-xs-6">
                    <RadioButtonGroup
                        name="employeeCollectiveMoshavMember"
                        label={t("form101.cop_member")}
                        options={isKibbutzMemberOptions}
                        value={values.employeeCollectiveMoshavMember}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        error={
                            touched.employeeCollectiveMoshavMember &&
                            errors.employeeCollectiveMoshavMember
                                ? errors.employeeCollectiveMoshavMember
                                : ""
                        }
                        required
                    />
                    {values.employeeCollectiveMoshavMember === "Yes" && (
                        <RadioButtonGroup
                            name="employeemyIncomeToKibbutz"
                            label={t("form101.myIncomeTrandfer")}
                            options={isKibbutzMemberOptions}
                            value={values.employeemyIncomeToKibbutz}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={
                                touched.employeemyIncomeToKibbutz &&
                                errors.employeemyIncomeToKibbutz
                                    ? errors.employeemyIncomeToKibbutz
                                    : ""
                            }
                            required
                        />
                    )}
                </div>
                <div className="col-lg-3 col-sm-4 col-xs-6">
                    <RadioButtonGroup
                        name="employeeHealthFundMember"
                        label={t("form101.healthFundMem")}
                        options={isHealthFundMemberOptions}
                        value={values.employeeHealthFundMember}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        error={
                            touched.employeeHealthFundMember &&
                            errors.employeeHealthFundMember
                                ? errors.employeeHealthFundMember
                                : ""
                        }
                        required
                    />
                    {values.employeeHealthFundMember === "Yes" && (
                        <RadioButtonGroup
                            name="employeeHealthFundname"
                            label={t("form101.HealthFundName")}
                            options={HealthFundname}
                            value={values.employeeHealthFundname}
                            onChange={handleChange}
                            onBlur={handleBlur}
                            error={
                                touched.employeeHealthFundname &&
                                errors.employeeHealthFundname
                                    ? errors.employeeHealthFundname
                                    : ""
                            }
                            required
                        />
                    )}
                </div>
            </div>
        </div>
    );
}
