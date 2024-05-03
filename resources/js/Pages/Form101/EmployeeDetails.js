import React from "react";
import TextField from "./inputElements/TextField";
import SelectElement from "./inputElements/SelectElement";
import DateField from "./inputElements/DateField";
import RadioButtonGroup from "./inputElements/RadioButtonGroup";
import { useTranslation } from "react-i18next";
import { cityOption, countryOption } from "./cityCountry";

const sexOptions = [
    { label: "Male", value: "Male" },
    { label: "Female", value: "Female" },
];

const maritalStatusOptions = [
    { label: "Single", value: "Single" },
    { label: "Married", value: "Married" },
    { label: "Divorced", value: "Divorced" },
    { label: "Widowed", value: "Widowed" },
    { label: "Separated", value: "Separated" },
];

const isIsraeliResidentOptions = [
    { label: "Yes", value: "Yes" },
    { label: "No", value: "No" },
];

const isKibbutzMemberOptions = [
    { label: "Yes", value: "Yes" },
    { label: "No", value: "No" },
];

const isHealthFundMemberOptions = [
    { label: "Yes", value: "Yes" },
    { label: "No", value: "No" },
];
const HealthFundname = [
    { label: "Clalit", value: "Clalit" },
    { label: "Maccabi", value: "Maccabi" },
    { label: "Meuhedet", value: "Meuhedet" },
    { label: "Leumit", value: "Leumit" },
];

export default function EmployeeDetails({
    errors,
    values,
    touched,
    handleBlur,
    handleChange,
    setFieldValue,
}) {
    const { t } = useTranslation();

    return (
        <div>
            <h2>{t("form101.employee_details")}</h2>
            <div className="row">
                <div className="col-sm-6 col-xs-6">
                    <TextField
                        name="employeeFirstName"
                        label="First Name"
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
                        label="Last Name"
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
                        Identify by *
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
                        ID Number
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
                        Passport
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
                        <div className="col-sm-4 col-xs-6">
                            <SelectElement
                                name={"employeecountry"}
                                label={"Country"}
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
                                    label="Passport Number"
                                    value={values.employeePassportNumber}
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
                            <div>
                                <label htmlFor="employeepassportCopy">
                                    Photo copy of Passport
                                </label>
                                <br />
                                <input
                                    type="file"
                                    name="employeepassportCopy"
                                    id="employeepassportCopy"
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
                        <div className="col-sm-4 col-xs-6">
                            <label htmlFor="employeeResidencePermit">
                                Photocopy of residence permit in Israel for a
                                foreign employee{" "}
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
                        <div className="col-sm-4 col-xs-6">
                            <TextField
                                name="employeeIdNumber"
                                label="ID Number"
                                value={values.employeeIdNumber}
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
                                Photo copy of ID card
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
                        label="Date of birth"
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
                            label="Date of Aliyah"
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
                <div className="col-sm-3 col-xs-6">
                    <SelectElement
                        name={"employeeCity"}
                        label={"City"}
                        value={values.employeeCity}
                        onChange={handleChange}
                        onBlur={handleBlur}
                        error={
                            touched.employeeCity && errors.employeeCity
                                ? errors.employeeCity
                                : ""
                        }
                        options={cityOption}
                        required={true}
                    />
                </div>
                <div className="col-sm-3 col-xs-6">
                    <TextField
                        name="employeeStreet"
                        label="Street"
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
                <div className="col-sm-3 col-xs-6">
                    <TextField
                        name="employeeHouseNo"
                        label="House number"
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
                <div className="col-sm-3 col-xs-6">
                    <TextField
                        name="employeePostalCode"
                        label="Postal Code"
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
                <div className="col-6">
                    <TextField
                        name="employeeMobileNo"
                        label="Mobile number"
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
                <div className="col-6">
                    <TextField
                        name="employeePhoneNo"
                        label="Phone number"
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
                        label="Email"
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
                <div className="col-sm-2 col-xs-6">
                    <RadioButtonGroup
                        name="employeeSex"
                        label="Sex"
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
                <div className="col-sm-2 col-xs-6">
                    <RadioButtonGroup
                        name="employeeMaritalStatus"
                        label="Marital status"
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
                <div className="col-sm-2 col-xs-6">
                    <RadioButtonGroup
                        name="employeeIsraeliResident"
                        label="Israeli resident"
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
                <div className="col-sm-3 col-xs-6">
                    <RadioButtonGroup
                        name="employeeCollectiveMoshavMember"
                        label="Kibbutz / Collective moshav member"
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
                            label="My income from this employer is transferred to the Kibbutz"
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
                <div className="col-sm-3 col-xs-6">
                    <RadioButtonGroup
                        name="employeeHealthFundMember"
                        label="Health fund member"
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
                            label="Health fund name"
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
