import React, { useEffect, useState } from "react";
import TextField from "./inputElements/TextField";
import SelectElement from "./inputElements/SelectElement";
import DateField from "./inputElements/DateField";
import RadioButtonGroup from "./inputElements/RadioButtonGroup";
import { useTranslation } from "react-i18next";
import { countryOption } from "./cityCountry";


export default function GeneralInfo({
    errors,
    values,
    touched,
    handleBlur,
    handleChange,
    setFieldValue,
    handleBubbleToggle,
    activeBubble,
    handleFileChange
}) {

    const [indentityType, setIndentityType] = useState(values.employeecountry);
    useEffect(() => {
        if (values.employeecountry === "Israel") {
            setIndentityType("IDNumber");
        }
        // Only update identity type if it's not yet set or country changes to Israel
        if (values.employeeIdentityType === "" || values.employeecountry !== "Israel") {
            setIndentityType("Passport");
        }
    }, [values.employeecountry]);


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

    const tips = {
        "First Name": " Enter your full first name as it appears on your identification document.",
        "Last Name": "Enter your full last name as it appears on your identification document.",
        "Identification By": "Select the type of identification you are providing (e.g., ID card, passport).",
        "Passport": "If applicable, enter your passport number and country of issue.",
        "ID Number": "If applicable, enter your ID number and country of issue.",
        "Photocopy Of ID Card": "Attach a clear photocopy of your identification card.",
        "Date Of Birth": "Enter your date of birth in DD/MM/YYYY format.",
        "Date Of Immigration": "If you are an immigrant, enter the date of your immigration to Israel in DD/MM/YYYY format.",
        "City": "Enter the city of your current residence.",
        "Street": "Enter the street name of your current residence.",
        "House Number": "Enter the house or building number of your current residence.",
        "Postal Code": " Enter the postal code of your current address.",
        "Phone Number": "Enter your landline phone number, if applicable.",
        "Mobile Number": "Enter your mobile phone number.",
        "Email": "Enter your active email address for contact purposes.",
        "Start Date Of Job": " Enter the date you started working for your current employer in DD/MM/YYYY format.",
        "Role": "Specify your current job role or position.",
        "Sex": "Select your gender (Male/Female).",
        "Israeli Resident": "Indicate whether you are an Israeli resident (Yes/No).",
        "Member Of Kibbutz/Cooperative Session": "Mark if you are a member of a kibbutz or cooperative association.",
        "Health Fund Number": " Enter your health fund membership number, if applicable.",
        "Marital Status": " Select your marital status (e.g., Single, Married, Divorced)."
    }

    const save = (data) => {
        axios
            .post(`/api/admin/document/save`, data, { headers })
            .then((res) => {
                if (res.data.errors) {
                    for (let e in res.data.errors) {
                        alert.error(res.data.errors[e][0]);
                    }
                } else {
                    alert.success(res.data.message);
                }
            })
            .catch((err) => {
                alert.error("Error!");
            });
    };

    return (
        <div className="">
            <p className="navyblueColor font-34 mt-4 font-w-500">Welcome to Broom Service!</p>
            <div className="row mt-3">
                <section className="col-xl">
                    <p className="font-w-500">
                        We are glad that you chose to work in our company. We will do everything to make you happy and satisfied with your work, and of course take care of everything you need just like a family. The job is full-time approximately 8 hours a day and Fridays are optional
                    </p>
                    <div className="mt-3 lightgrey p-4" style={{ borderRadius: "3px" }}>
                        <p className="mb-3 font-w-500 font-20">Tips For Filling Out The Form</p>
                        <div>
                            {Object.entries(tips).map(([key, value], index) => (
                                <p className="mb-1" key={index}>
                                    <span className="font-w-500">{key} - </span>
                                    {value}
                                </p>
                            ))}
                        </div>
                    </div>
                </section>
                <section className="col p-4">
                    <p className="mb-3 font-w-500 font-20">Employee General Information</p>
                    {/* GeneralInformation */}
                    <div className="box-heading">
                        <div className="">
                            <div className="row justify-content-between">
                                <div className="col-sm" style={{ position: 'relative' }}>
                                    <TextField
                                        name="employeeFirstName"
                                        label={t("form101.label_firstName")}
                                        value={values.employeeFirstName}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        readonly={true}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        error={
                                            touched.employeeFirstName && errors.employeeFirstName
                                                ? errors.employeeFirstName
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeeFirstName' && (
                                        <div className="d-flex justify-content-end" style={{ position: 'relative' }}>
                                            <div className="speech up">
                                                Enter your full first name as it appears on your identification document.
                                            </div>
                                        </div>
                                    )}
                                </div>
                                <div className="col-sm">
                                    <TextField
                                        name="employeeLastName"
                                        label={t("form101.label_lastName")}
                                        value={values.employeeLastName}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        readonly={true}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        error={
                                            touched.employeeLastName && errors.employeeLastName
                                                ? errors.employeeLastName
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeeLastName' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                Enter your full last name as it appears on your identification document.
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm" id="employeeIsraeliResident">
                                    <RadioButtonGroup
                                        name="employeeIsraeliResident"
                                        label={t("form101.israeli_resident")}
                                        options={isIsraeliResidentOptions}
                                        value={values.employeeIsraeliResident}
                                        onChange={handleChange}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        onBlur={handleBlur}
                                        isFlex={true}
                                        error={
                                            touched.employeeIsraeliResident &&
                                                errors.employeeIsraeliResident
                                                ? errors.employeeIsraeliResident
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeeIsraeliResident' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                Indicate whether you are an Israeli resident (Yes/No).
                                            </div>
                                        </div>
                                    )}
                                </div>
                                <div className="col-sm">
                                    <RadioButtonGroup
                                        name="employeeSex"
                                        label={t("form101.label_sex")}
                                        options={sexOptions}
                                        value={values.employeeSex}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        isFlex={true}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        error={
                                            touched.employeeSex && errors.employeeSex
                                                ? errors.employeeSex
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeeSex' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                Select your gender (Male/Female).
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm">
                                    <label className="control-label d-block">
                                        {t("form101.idBy")} *
                                    </label>
                                    <div className="d-flex">
                                        <label className="radio">
                                            <input
                                                type="radio"
                                                name="employeeIdentityType"
                                                value="IDNumber"
                                                className="mr-2"
                                                checked={indentityType === "IDNumber" ? true : false}
                                                onChange={(e) => {
                                                    setIndentityType(e.target.value);
                                                    handleChange(e);
                                                    setFieldValue("employeeIdentityType", e.target.value);
                                                }}
                                            />
                                            <span className="">{t("form101.id_num")}</span>
                                        </label>
                                        <label className="radio mt-0">
                                            <input
                                                type="radio"
                                                name="employeeIdentityType"
                                                value="Passport"
                                                className="mr-2"
                                                checked={indentityType === "Passport" ? true : false}
                                                onChange={(e) => {
                                                    setIndentityType(e.target.value);
                                                    handleChange(e);
                                                    setFieldValue("employeeIdentityType", e.target.value);

                                                }}
                                            />
                                            <span className="">{t("form101.passport_foreign")}</span>
                                        </label>
                                    </div>
                                    {touched.employeeIdentityType &&
                                        errors.employeeIdentityType && (
                                            <p className="text-danger">
                                                {errors.employeeIdentityType}
                                            </p>
                                        )}
                                </div>
                                <div className="col-sm">
                                    <SelectElement
                                        name={"employeecountry"}
                                        label={t("form101.country_passport")}
                                        value={values.employeecountry}
                                        onChange={(e) => {
                                            const selectedCountry = e.target.value;
                                            handleChange(e);

                                            // Set identity type based on country, but only modify the identity field
                                            if (selectedCountry === "Israel") {
                                                setIndentityType("IDNumber");
                                                setFieldValue("employeeIdentityType", "IDNumber");
                                            } else {
                                                setIndentityType("Passport");
                                                setFieldValue("employeeIdentityType", "Passport");
                                            }

                                            setFieldValue("employeecountry", selectedCountry); // Update country
                                        }}
                                        onBlur={handleBlur}
                                        error={
                                            touched.employeecountry && errors.employeecountry
                                                ? errors.employeecountry
                                                : ""
                                        }
                                        options={countryOption}
                                    />
                                </div>
                            </div>
                            {indentityType === "Passport" ? (
                                <>

                                    <div className="row">
                                        <div className="col-sm">
                                            <TextField
                                                name="employeePassportNumber"
                                                label={t("form101.passport_num")}
                                                value={values.employeecountry !== "Israel" ? values.employeePassportNumber : ""}
                                                onChange={handleChange}
                                                onBlur={handleBlur}
                                                toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                                error={
                                                    touched.employeePassportNumber &&
                                                        errors.employeePassportNumber
                                                        ? errors.employeePassportNumber
                                                        : ""
                                                }
                                                required
                                            // readonly={values.employeePassportNumber === null ? false : true}
                                            />
                                            {activeBubble === 'employeePassportNumber' && (
                                                <div className="d-flex justify-content-end">
                                                    <div className="speech up">
                                                        If applicable, enter your passport number and country of issue.
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                        <div className="col-sm">
                                            <label htmlFor="employeepassportCopy">
                                                {t("form101.passport_photo")}
                                            </label>
                                            <br />
                                            <div className="input_container">
                                                <input
                                                    type="file"
                                                    name="employeepassportCopy"
                                                    id="employeepassportCopy"
                                                    accept="image/*"
                                                    onChange={(e) => {
                                                        setFieldValue(
                                                            "employeepassportCopy",
                                                            e.target.files[0]
                                                        );
                                                        handleFileChange(e, "passport");
                                                    }
                                                    }
                                                    onBlur={handleBlur}
                                                />
                                            </div>
                                            {touched.employeepassportCopy &&
                                                errors.employeepassportCopy && (
                                                    <p className="text-danger">
                                                        {errors.employeepassportCopy}
                                                    </p>
                                                )}
                                        </div>
                                    </div>
                                    <div className="row mt-3 mb-4 ">
                                        <div className="col-sm">
                                            <label htmlFor="employeeResidencePermit"
                                                style={{ marginBottom: "0", width: "100%" }}
                                            >
                                                {t("form101.PhotoCopyResident")}
                                            </label>
                                        </div>
                                        <div className="col-sm">
                                            <div className="input_container" style={{ height: "42px" }}>
                                                <input
                                                    type="file"
                                                    name="employeeResidencePermit"
                                                    id="employeeResidencePermit"
                                                    className="form-control man p-0 border-0"
                                                    style={{ fontSize: "unset", backgroundColor: "unset", }}
                                                    accept="image/*"
                                                    onChange={(e) =>
                                                        setFieldValue(
                                                            "employeeResidencePermit",
                                                            e.target.files[0]
                                                        )
                                                    }
                                                    onBlur={handleBlur}
                                                />
                                            </div>
                                            {touched.employeeResidencePermit &&
                                                errors.employeeResidencePermit && (
                                                    <p className="text-danger">
                                                        {errors.employeeResidencePermit}
                                                    </p>
                                                )}
                                        </div>
                                    </div>
                                </>
                            ) : (
                                <>
                                    <div className="row">
                                        <div className="col-sm">
                                            <TextField
                                                name="employeeIdNumber"
                                                label={t("form101.id_num")}
                                                value={values.employeecountry === "Israel" ? values.employeeIdNumber : ""}
                                                className="form-control man p-0 border-0"
                                                style={{ fontSize: "unset", backgroundColor: "unset", }}
                                                onChange={handleChange}
                                                onBlur={handleBlur}
                                                toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                                error={
                                                    touched.employeeIdNumber &&
                                                        errors.employeeIdNumber
                                                        ? errors.employeeIdNumber
                                                        : ""
                                                }
                                                required
                                            // readonly={values.employeeIdNumber === null ? false : true}
                                            />
                                            {activeBubble === 'employeeIdNumber' && (
                                                <div className="d-flex justify-content-end">
                                                    <div className="speech up">
                                                        If applicable, enter your ID number and country of issue.
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                        <div className="col-sm mb-2">
                                            <label htmlFor="employeeIdCardCopy">
                                                {t("form101.id_photocopy")}
                                            </label>
                                            <br />
                                            <div className="input_container">
                                                <input
                                                    type="file"
                                                    name="employeeIdCardCopy"
                                                    id="employeeIdCardCopy"
                                                    accept="image/*"
                                                    onChange={(e) => {
                                                        setFieldValue(
                                                            "employeeIdCardCopy",
                                                            e.target.files[0]
                                                        );
                                                        handleFileChange(e, "id_card");
                                                    }
                                                    }
                                                    onBlur={handleBlur}
                                                />
                                            </div>
                                            {touched.employeeIdCardCopy &&
                                                errors.employeeIdCardCopy && (
                                                    <p className="text-danger">
                                                        {errors.employeeIdCardCopy}
                                                    </p>
                                                )}
                                        </div>
                                    </div>

                                </>
                            )}
                        </div>
                        <div className="">
                            <div className="row">
                                <div className="col-sm">
                                    <DateField
                                        name="employeeDob"
                                        label={t("form101.dob")}
                                        value={values.employeeDob}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        error={
                                            touched.employeeDob && errors.employeeDob
                                                ? errors.employeeDob
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeeDob' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                Enter your date of birth in DD/MM/YYYY format.
                                            </div>
                                        </div>
                                    )}
                                </div>
                                <div className={`w-100 ${indentityType === "Passport" ? "d-none" : 'd-block col-sm'}`}>
                                    {indentityType === "IDNumber" && (
                                        <>
                                            <DateField
                                                name="employeeDateOfAliyah"
                                                label={t("form101.dom")}
                                                value={values.employeeDateOfAliyah}
                                                onChange={handleChange}
                                                onBlur={handleBlur}
                                                toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                                error={
                                                    touched.employeeDateOfAliyah &&
                                                        errors.employeeDateOfAliyah
                                                        ? errors.employeeDateOfAliyah
                                                        : ""
                                                }
                                            />
                                            {activeBubble === 'employeeDateOfAliyah' && (
                                                <div className="d-flex justify-content-end">
                                                    <div className="speech up">
                                                        Enter the date you started working for your current employer in DD/MM/YYYY format.
                                                    </div>
                                                </div>
                                            )}
                                        </>
                                    )}
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm">
                                    <TextField
                                        name="employeeCity"
                                        label={t("form101.City")}
                                        value={values.employeeCity}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        error={
                                            touched.employeeCity && errors.employeeCity
                                                ? errors.employeeCity
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeeCity' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                Enter the city of your current residence.
                                            </div>
                                        </div>
                                    )}
                                </div>
                                <div className="col-sm">
                                    <TextField
                                        name="employeeStreet"
                                        label={t("form101.street")}
                                        value={values.employeeStreet}
                                        onChange={handleChange}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        onBlur={handleBlur}
                                        error={
                                            touched.employeeStreet && errors.employeeStreet
                                                ? errors.employeeStreet
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeeStreet' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                Enter the street name of your current residence.
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm">
                                    <TextField
                                        name="employeeHouseNo"
                                        label={t("form101.ho_num")}
                                        value={values.employeeHouseNo}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        error={
                                            touched.employeeHouseNo && errors.employeeHouseNo
                                                ? errors.employeeHouseNo
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeeHouseNo' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                Enter the house or building number of your current residence.
                                            </div>
                                        </div>
                                    )}
                                </div>
                                <div className="col-sm">
                                    <TextField
                                        name="employeePostalCode"
                                        label={t("form101.postal_code")}
                                        value={values.employeePostalCode}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        error={
                                            touched.employeePostalCode &&
                                                errors.employeePostalCode
                                                ? errors.employeePostalCode
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeePostalCode' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                Enter the postal code of your current address.
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm">
                                    <TextField
                                        name="employeeMobileNo"
                                        label={t("form101.mob_num")}
                                        value={values.employeeMobileNo}
                                        onChange={handleChange}
                                        readonly={true}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        onBlur={handleBlur}
                                        error={
                                            touched.employeeMobileNo && errors.employeeMobileNo
                                                ? errors.employeeMobileNo
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'employeeMobileNo' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                Enter your mobile phone number.
                                            </div>
                                        </div>
                                    )}
                                </div>
                                <div className="col-sm">
                                    <TextField
                                        name="employeePhoneNo"
                                        label={t("form101.label_phNum")}
                                        value={values.employeePhoneNo}
                                        onChange={handleChange}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        onBlur={handleBlur}
                                        error={
                                            touched.employeePhoneNo && errors.employeePhoneNo
                                                ? errors.employeePhoneNo
                                                : ""
                                        }
                                    />
                                    {activeBubble === 'employeePhoneNo' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                Enter your landline phone number, if applicable.
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm">
                                    <TextField
                                        name="employeeEmail"
                                        label={t("form101.label_email")}
                                        value={values.employeeEmail}
                                        onChange={handleChange}
                                        readonly={true}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        onBlur={handleBlur}
                                        error={
                                            touched.employeeEmail && errors.employeeEmail
                                                ? errors.employeeEmail
                                                : ""
                                        }
                                        required={true}
                                    />
                                    {activeBubble === 'employeeEmail' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                Enter your active email address for contact purposes.
                                            </div>
                                        </div>
                                    )}
                                </div>
                                <div className="col-sm">
                                    <DateField
                                        name="DateOfBeginningWork"
                                        label="Start Date of Job"
                                        value={values.DateOfBeginningWork}
                                        onChange={(e) => {
                                            if (e.target.value !== null) {
                                                setFieldValue("DateOfBeginningWork", e.target.value);
                                            } 
                                        }}
                                        toggleBubble={handleBubbleToggle} // Pass the toggle handler
                                        onBlur={handleBlur}
                                        error={
                                            touched.DateOfBeginningWork && errors.DateOfBeginningWork
                                                ? errors.DateOfBeginningWork
                                                : ""
                                        }
                                        required
                                    />
                                    {activeBubble === 'DateOfBeginningWork' && (
                                        <div className="d-flex justify-content-end">
                                            <div className="speech up">
                                                Enter the date you started working for your current employer in DD/MM/YYYY format.
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </div>
                            <div className="row">
                                <div className="col-sm">
                                    <RadioButtonGroup
                                        name="employeeHealthFundMember"
                                        label={t("form101.healthFundMem")}
                                        options={isHealthFundMemberOptions}
                                        value={values.employeeHealthFundMember}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        isFlex={true}
                                        error={
                                            touched.employeeHealthFundMember &&
                                                errors.employeeHealthFundMember
                                                ? errors.employeeHealthFundMember
                                                : ""
                                        }
                                        required
                                    />
                                </div>
                                {values.employeeHealthFundMember === "Yes" && (
                                    <div className="col-sm">
                                        <RadioButtonGroup
                                            name="employeeHealthFundname"
                                            label={t("form101.HealthFundName")}
                                            options={HealthFundname}
                                            value={values.employeeHealthFundname}
                                            onChange={handleChange}
                                            onBlur={handleBlur}
                                            isFlex={true}
                                            error={
                                                touched.employeeHealthFundname &&
                                                    errors.employeeHealthFundname
                                                    ? errors.employeeHealthFundname
                                                    : ""
                                            }
                                            required
                                        />
                                    </div>
                                )}
                            </div>

                            <div className="row">
                                <div className="col-sm">
                                    <RadioButtonGroup
                                        name="employeeCollectiveMoshavMember"
                                        label={t("form101.cop_member")}
                                        options={isKibbutzMemberOptions}
                                        value={values.employeeCollectiveMoshavMember}
                                        onChange={handleChange}
                                        onBlur={handleBlur}
                                        isFlex={true}
                                        error={
                                            touched.employeeCollectiveMoshavMember &&
                                                errors.employeeCollectiveMoshavMember
                                                ? errors.employeeCollectiveMoshavMember
                                                : ""
                                        }
                                        required
                                    />
                                </div>
                                {values.employeeCollectiveMoshavMember === "Yes" && (
                                    <div className="col-sm">
                                        <RadioButtonGroup
                                            name="employeemyIncomeToKibbutz"
                                            label={t("form101.myIncomeTrandfer")}
                                            options={isKibbutzMemberOptions}
                                            value={values.employeemyIncomeToKibbutz}
                                            onChange={handleChange}
                                            onBlur={handleBlur}
                                            isFlex={true}
                                            error={
                                                touched.employeemyIncomeToKibbutz &&
                                                    errors.employeemyIncomeToKibbutz
                                                    ? errors.employeemyIncomeToKibbutz
                                                    : ""
                                            }
                                            required
                                        />
                                    </div>
                                )}
                            </div>

                            <div className="">
                                <RadioButtonGroup
                                    name="employeeMaritalStatus"
                                    label={t("form101.martial_status")}
                                    options={maritalStatusOptions}
                                    resClassName="status"
                                    value={values.employeeMaritalStatus}
                                    onChange={handleChange}
                                    onBlur={handleBlur}
                                    isFlex={true}
                                    error={
                                        touched.employeeMaritalStatus &&
                                            errors.employeeMaritalStatus
                                            ? errors.employeeMaritalStatus
                                            : ""
                                    }
                                    required
                                />
                            </div>
                        </div>
                    </div>

                </section>
            </div>
        </div >
    );
}
